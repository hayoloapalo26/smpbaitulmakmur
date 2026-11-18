<?php

namespace App\Filament\Staff\Resources\QuranAchievementResource\Pages;

use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Models\QuranAchievement;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageQuranAchievements extends ManageRecords
{
    protected static string $resource = QuranAchievementResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->has('open_rombel') || request()->has('open_batch')) {
            $this->defaultAction = 'input_setoran_rombel';
            $this->mountAction('input_setoran_rombel');
        }
    }

    protected function getHeaderActions(): array
    {
        $surahs = QuranAchievementResource::getSurahs();

        return [
            Actions\Action::make('input_setoran_rombel')
                ->label('⚡ Input Penilaian & Setoran Rombel (Jurnal Mengajar)')
                ->modalHeading('📖 Input Penilaian Kualitas Bacaan & Setoran Santri Binaan')
                ->modalSubheading('Data kelas dan santri binaan otomatis ditentukan dari penempatan Waka Kurikulum (tanpa perlu memilih kelas). Atur materi setoran, skor kualitas bacaan (0-100), predikat kelulusan, dan catatan evaluasi per santri di lembar bawah.')
                ->color('primary')
                ->icon('heroicon-o-user-group')
                ->modalWidth('7xl')
                ->modalSubmitActionLabel('💾 Simpan Penilaian & Setoran Seluruh Santri')
                ->form([
                    Forms\Components\Section::make('📖 1. Parameter Penilaian Al-Qur\'an (Otomatis dari Waka Kurikulum)')
                        ->description('Data santri binaan termuat otomatis sesuai penempatan kelas oleh Waka Kurikulum.')
                        ->schema([
                            // 1. Informasi Otomatis untuk Guru Al-Qur'an (Tanpa Dropdown!)
                            Forms\Components\Placeholder::make('info_kelas_otomatis')
                                ->label('Kelompok / Kelas Al-Qur\'an')
                                ->content(function () {
                                    $user = Auth::user();
                                    $label = $user?->getQuranSpecializationLabel() ?? 'Al-Qur\'an';
                                    $teacherName = $user?->name ?? 'Guru Pengampu';
                                    return new \Illuminate\Support\HtmlString("
                                        <div class='flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 font-bold text-xs'>
                                            <span class='text-base'>📖</span>
                                            <div>
                                                <div>Kelas {$label} • Dibina oleh: {$teacherName}</div>
                                                <div class='text-[10px] text-emerald-600 dark:text-emerald-400 font-normal'>Daftar santri binaan otomatis dimuat dari penempatan Waka Kurikulum</div>
                                            </div>
                                        </div>
                                    ");
                                })
                                ->visible(fn () => (Auth::user()?->isQuranTeacher() && !Auth::user()?->hasAnyRole(['waka_kurikulum', 'superadmin'])) ?? true)
                                ->columnSpan(fn () => 2),

                            // Hidden input untuk menyimpan value program otomatis
                            Forms\Components\Hidden::make('program_otomatis')
                                ->default(fn () => Auth::user()?->getQuranSpecializationKey() ?? 'tahsin'),

                            // Dropdown HANYA untuk Waka Kurikulum / Superadmin
                            Forms\Components\Select::make('program')
                                ->label('Pilih Kelompok / Kelas Al-Qur\'an')
                                ->options([
                                    'yanbua'           => '📖 Kelas Yanbu\'a (Metode Baca Tulis Al-Qur\'an)',
                                    'tahsin'           => '📖 Kelas Tahsin (Kualitas & Kaidah Tajwid)',
                                    'tahfidz_dasar'    => '📖 Kelas Tahfidz Dasar (Juz 30 / Juz \'Amma)',
                                    'tahfidz_lanjutan' => '📖 Kelas Tahfidz Lanjutan (Juz 1-29)',
                                    'tahfidz'          => '📖 Kelas Tahfidz Al-Qur\'an (Umum)',
                                ])
                                ->default(fn () => Auth::user()?->getQuranSpecializationKey() ?? 'tahsin')
                                ->required()
                                ->live()
                                ->visible(fn () => Auth::user()?->hasAnyRole(['waka_kurikulum', 'superadmin']) ?? false)
                                ->columnSpan(fn () => 2),

                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal Penilaian')
                                ->default(Carbon::today())
                                ->disabled()
                                ->dehydrated()
                                ->required()
                                ->helperText('Otomatis paten hari ini (' . Carbon::today()->translatedFormat('l, d/m/Y') . ')')
                                ->columnSpan(fn () => 1),
                        ])->columns(3),

                    Forms\Components\Section::make('👥 2. Lembar Penilaian & Setoran Santri (Format Jurnal Mengajar)')
                        ->description('Format terintegrasi satu pintu: Capaian Ziyadah (Juz, Surat, Ayat), Capaian Muraja\'ah (Juz, Surat, Ayat), Skor Kualitas Bacaan (0-100), Predikat, dan Catatan Guru.')
                        ->schema([
                            Forms\Components\ViewField::make('student_achievements')
                                ->view('filament.staff.forms.components.quran-achievement-batch-selector')
                                ->viewData(function (Forms\Get $get) use ($surahs) {
                                    $user = Auth::user();
                                    
                                    // Deteksi program otomatis
                                    $program = null;
                                    if ($user?->isQuranTeacher()) {
                                        $program = $user->getQuranSpecializationKey();
                                    }
                                    if (empty($program)) {
                                        $program = $get('program') ?? $get('program_otomatis') ?? 'yanbua';
                                    }

                                    $query = Student::withoutGlobalScopes()->with(['classroom', 'guruQuran']);

                                    // Otomatisasi data santri binaan dari Waka Kurikulum (tersendirikan per program)
                                    if ($user?->isQuranTeacher() && !$user?->hasAnyRole(['waka_kurikulum', 'superadmin'])) {
                                        $query->where(function ($q) use ($user, $program) {
                                            // 1. Prioritas Utama: Santri yang telah ditempatkan ke guru ini oleh Waka Kurikulum
                                            $q->where('guru_quran_id', $user->id);

                                            // 2. ATAU santri pada kelompok Al-Qur'an ini yang belum ditentukan guru spesifiknya
                                            if ($program) {
                                                $q->orWhere(function ($sub) use ($program) {
                                                    $sub->where(function ($p) use ($program) {
                                                        $p->where('kelas_quran', $program)
                                                          ->orWhere('kelas_quran', 'like', "%{$program}%");
                                                    })->whereNull('guru_quran_id');
                                                });
                                            }
                                        });
                                    } elseif ($program) {
                                        $query->where(function ($p) use ($program) {
                                            $p->where('kelas_quran', $program)
                                              ->orWhere('kelas_quran', 'like', "%{$program}%");
                                        });
                                    }

                                    $students = $query->orderBy('name')->get();

                                    // Fallback cerdas: Jika query kosong namun user adalah guru Al-Qur'an,
                                    // muat langsung seluruh santri yang diampu guru_quran_id = user->id
                                    if ($students->isEmpty() && $user?->isQuranTeacher()) {
                                        $students = Student::withoutGlobalScopes()
                                            ->where('guru_quran_id', $user->id)
                                            ->with(['classroom', 'guruQuran'])
                                            ->orderBy('name')
                                            ->get();
                                    }

                                    // Ambil riwayat capaian setoran terakhir per santri untuk otomatisasi kelanjutan surat & ayat
                                    $studentIds = $students->pluck('id')->filter()->toArray();
                                    $latestAchievements = collect();
                                    $latestMurajaahs = collect();
                                    if (!empty($studentIds)) {
                                        // 1. Ambil setoran Ziyadah riil terakhir (mengabaikan record 'tidak_menyetor')
                                        $latestAchievements = QuranAchievement::withoutGlobalScopes()
                                            ->whereIn('student_id', $studentIds)
                                            ->whereNotNull('surah')
                                            ->where('surah', '!=', '')
                                            ->whereNotIn('surah', ['tidak_menyetor', 'Tidak Menyetor', '-'])
                                            ->orderBy('tanggal', 'desc')
                                            ->orderBy('id', 'desc')
                                            ->get()
                                            ->groupBy('student_id')
                                            ->map(fn ($group) => $group->first());

                                        // 2. Ambil setoran Muraja'ah riil terakhir (mengabaikan record 'tidak_menyetor')
                                        $latestMurajaahs = QuranAchievement::withoutGlobalScopes()
                                            ->whereIn('student_id', $studentIds)
                                            ->whereNotNull('surah_murajaah')
                                            ->where('surah_murajaah', '!=', '')
                                            ->whereNotIn('surah_murajaah', ['tidak_menyetor', 'Tidak Menyetor', '-'])
                                            ->orderBy('tanggal', 'desc')
                                            ->orderBy('id', 'desc')
                                            ->get()
                                            ->groupBy('student_id')
                                            ->map(fn ($group) => $group->first());
                                    }

                                    foreach ($students as $student) {
                                        $last = $latestAchievements->get($student->id);
                                        $lastMurajaah = $latestMurajaahs->get($student->id);

                                        if ($last && $lastMurajaah && (empty($last->surah_murajaah) || in_array($last->surah_murajaah, ['tidak_menyetor', 'Tidak Menyetor', '-']))) {
                                            $last->juz_murajaah = $lastMurajaah->juz_murajaah;
                                            $last->surah_murajaah = $lastMurajaah->surah_murajaah;
                                            $last->ayah_murajaah = $lastMurajaah->ayah_murajaah;
                                        } elseif (!$last && $lastMurajaah) {
                                            $last = $lastMurajaah;
                                        }

                                        $student->progress_data = QuranAchievement::calculateNextProgress($last, $program, $student->id);
                                    }

                                    $isTahsin = ($program === 'tahsin' || str_contains(strtolower((string)$program), 'tahsin'));

                                    return [
                                        'students'              => $students,
                                        'program'               => $program,
                                        'isTahsin'              => $isTahsin,
                                        'jilidOptions'          => QuranAchievement::getJilidOptions(),
                                        'defaultScaleScore'     => 85,
                                        'defaultJuz'            => $isTahsin ? null : 30,
                                        'defaultSurah'          => $isTahsin ? 'Jilid 1' : '78',
                                        'defaultAyah'           => $isTahsin ? 'Hal. 1-5' : '1-10',
                                        'defaultJuzMurajaah'    => '',
                                        'defaultSurahMurajaah'  => '',
                                        'defaultAyahMurajaah'   => '',
                                        'defaultType'           => 'ziyadah',
                                        'defaultJilidHalaman'   => '',
                                        'surahs'                => $surahs,
                                    ];
                                })
                                ->dehydrated(true)
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data) {
                    $program = $data['program'] ?? $data['program_otomatis'] ?? Auth::user()?->getQuranSpecializationKey() ?? 'tahsin';
                    $tanggal = $data['tanggal'] ?? Carbon::now();
                    $defaultScaleScore = !empty($data['default_scale_score']) ? (int)$data['default_scale_score'] : 85;
                    $defaultJuz = $data['default_juz'] ?? 30;
                    $defaultSurah = $data['default_surah'] ?? '78';
                    $defaultAyah = $data['default_ayah'] ?? '1-10';
                    $defaultJuzMurajaah = !empty($data['default_juz_murajaah']) ? (int)$data['default_juz_murajaah'] : null;
                    $defaultSurahMurajaah = !empty($data['default_surah_murajaah']) ? (string)$data['default_surah_murajaah'] : null;
                    $defaultAyahMurajaah = !empty($data['default_ayah_murajaah']) ? (string)$data['default_ayah_murajaah'] : null;
                    $studentAchievements = $data['student_achievements'] ?? [];
                    $teacherId = Auth::id();

                    if (empty($studentAchievements) || !is_array($studentAchievements)) {
                        Notification::make()
                            ->title('Gagal Menyimpan')
                            ->body('Lembar penilaian belum terisi. Silakan centang santri yang dinilai.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $savedCount = 0;

                    DB::transaction(function () use (
                        $studentAchievements,
                        $program,
                        $tanggal,
                        $defaultScaleScore,
                        $defaultJuz,
                        $defaultSurah,
                        $defaultAyah,
                        $defaultJuzMurajaah,
                        $defaultSurahMurajaah,
                        $defaultAyahMurajaah,
                        $teacherId,
                        &$savedCount
                    ) {
                        foreach ($studentAchievements as $studentId => $row) {
                            if (!is_array($row)) continue;

                            // Jika santri tidak dicentang (active == false), lewati
                            $isActive = $row['active'] ?? false;
                            if (!$isActive && $isActive !== '1' && $isActive !== 1) {
                                continue;
                            }

                            $student = Student::withoutGlobalScopes()->find($studentId);
                            if (!$student) continue;

                            $instId = $student->institution_id ?: Auth::user()?->institution_id ?: 1;
                            $studentProgram = $student->kelas_quran ?: $program;
                            $isTahsinRow = ($studentProgram === 'tahsin' || str_contains(strtolower((string)$studentProgram), 'tahsin'));

                            // 1. Ziyadah: Cek apakah hari ini tidak menyetorkan
                            $rawSurah = $row['surah'] ?? null;
                            $isZiyadahTidakMenyetor = in_array($rawSurah, ['tidak_menyetor', 'Tidak Menyetor', '-']);

                            if ($isZiyadahTidakMenyetor) {
                                $juz = null;
                                $surah = 'tidak_menyetor';
                                $ayah = '-';
                            } else {
                                $juz = !empty($row['juz']) && $row['juz'] !== '-' ? (int)$row['juz'] : ($isTahsinRow ? null : (int)$defaultJuz);
                                $surah = !empty($row['surah']) ? (string)$row['surah'] : ($isTahsinRow ? 'Jilid 1' : (string)$defaultSurah);
                                $ayah = !empty($row['ayah']) ? (string)$row['ayah'] : ($isTahsinRow ? 'Hal. 1-5' : (string)$defaultAyah);
                            }

                            // 2. Muraja'ah: Cek apakah hari ini tidak menyetorkan atau multiple choice
                            $rawSurahMurajaah = $row['surah_murajaah'] ?? null;
                            $isMurajaahTidakMenyetor = false;

                            if (is_array($rawSurahMurajaah)) {
                                $cleanMurajaahArr = array_values(array_filter($rawSurahMurajaah, fn($v) => !empty($v)));
                                if (in_array('tidak_menyetor', $cleanMurajaahArr) || in_array('-', $cleanMurajaahArr)) {
                                    $isMurajaahTidakMenyetor = true;
                                } elseif (empty($cleanMurajaahArr)) {
                                    $rawSurahMurajaah = null;
                                } else {
                                    $rawSurahMurajaah = implode(', ', $cleanMurajaahArr);
                                }
                            } else {
                                $isMurajaahTidakMenyetor = in_array($rawSurahMurajaah, ['tidak_menyetor', 'Tidak Menyetor', '-']);
                            }

                            if ($isMurajaahTidakMenyetor) {
                                $juzMurajaah = null;
                                $surahMurajaah = 'tidak_menyetor';
                                $ayahMurajaah = '-';
                            } elseif (!empty($rawSurahMurajaah)) {
                                $juzMurajaah = !empty($row['juz_murajaah']) && $row['juz_murajaah'] !== '-' ? (int)$row['juz_murajaah'] : ($isTahsinRow ? null : $defaultJuzMurajaah);
                                $surahMurajaah = (string)$rawSurahMurajaah;
                                $ayahMurajaah = !empty($row['ayah_murajaah']) ? (string)$row['ayah_murajaah'] : ($defaultAyahMurajaah ?: '1-Akhir');
                            } else {
                                $juzMurajaah = null;
                                $surahMurajaah = null;
                                $ayahMurajaah = null;
                            }

                            $scaleScore = isset($row['scale_score']) && $row['scale_score'] !== '' ? (int)$row['scale_score'] : $defaultScaleScore;
                            
                            $predikat = !empty($row['predikat']) ? (string)$row['predikat'] : null;
                            if (!$predikat) {
                                if ($scaleScore >= 90) $predikat = 'Mumtaz';
                                elseif ($scaleScore >= 80) $predikat = 'Jayyid Jiddan';
                                elseif ($scaleScore >= 70) $predikat = 'Jayyid';
                                else $predikat = 'Maqbul';
                            }

                            $catatan = $row['catatan'] ?? null;
                            if (empty($catatan)) {
                                if ($isZiyadahTidakMenyetor && $isMurajaahTidakMenyetor) {
                                    $catatan = 'Tidak ada setoran Al-Qur\'an hari ini.';
                                } elseif ($isZiyadahTidakMenyetor) {
                                    $catatan = 'Tidak ada setoran Ziyadah hari ini.';
                                } elseif ($isMurajaahTidakMenyetor) {
                                    $catatan = 'Tidak ada setoran Muraja\'ah hari ini.';
                                }
                            }

                            $jilid = $row['jilid_halaman'] ?? ($isTahsinRow ? ($isZiyadahTidakMenyetor ? 'Tidak Menyetor Hari Ini' : "{$surah} {$ayah}") : null);

                            $types = [];
                            if (!$isZiyadahTidakMenyetor) {
                                $types[] = 'ziyadah';
                            }
                            if (!$isMurajaahTidakMenyetor && !empty($surahMurajaah)) {
                                $types[] = 'murajaah';
                            }
                            if (empty($types)) {
                                $types = ['tidak_menyetor'];
                            }

                            QuranAchievement::create([
                                'institution_id'    => $instId,
                                'student_id'        => $student->id,
                                'teacher_id'        => $teacherId,
                                'program'           => $studentProgram,
                                'type'              => $types,
                                'juz'               => $juz,
                                'surah'             => $surah,
                                'ayah'              => $ayah,
                                'juz_murajaah'      => $juzMurajaah,
                                'surah_murajaah'    => $surahMurajaah,
                                'ayah_murajaah'     => $ayahMurajaah,
                                'jilid_halaman'     => $jilid,
                                'scale_score'       => $scaleScore,
                                'predikat'          => $predikat,
                                'catatan'           => $catatan,
                                'tanggal'           => $tanggal,
                                'status_verifikasi' => 'Menunggu Verifikasi',
                            ]);

                            $savedCount++;
                        }
                    });

                    if ($savedCount > 0) {
                        Notification::make()
                            ->title('Penilaian & Setoran Santri Berhasil Disimpan!')
                            ->body("Alhamdulillah, sebanyak {$savedCount} penilaian kualitas dan setoran santri binaan telah tersimpan dan terintegrasi otomatis.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Tidak Ada Data yang Disimpan')
                            ->body('Pastikan Anda mencentang setidaknya satu santri yang dinilai hari ini.')
                            ->warning()
                            ->send();
                    }
                }),

            Actions\Action::make('peringkat_tahfidz')
                ->label('🏆 Lihat Peringkat Tahfidz')
                ->icon('heroicon-o-trophy')
                ->color('info')
                ->url(fn () => \App\Filament\Staff\Pages\TahfidzRanking::getUrl()),
        ];
    }
}
