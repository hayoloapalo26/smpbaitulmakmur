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
                ->label('⚡ Input Setoran Rombel Al-Qur\'an (Format Jurnal Mengajar)')
                ->modalHeading('📖 Input Setoran Hafalan Sekelompok Santri (Format Jurnal Mengajar)')
                ->modalSubheading('Pilih Kelompok Al-Qur\'an, Tanggal, Tipe Setoran, serta Surat & Juz default satu kali di atas. Catat setoran dan evaluasi tiap santri di lembar bawah.')
                ->color('primary')
                ->icon('heroicon-o-user-group')
                ->modalWidth('7xl')
                ->modalSubmitActionLabel('💾 Simpan Setoran Seluruh Santri')
                ->form([
                    Forms\Components\Section::make('📖 1. Parameter Setoran Al-Qur\'an (Satu untuk Seluruh Santri)')
                        ->description('Pilihan di bawah berlaku sebagai acuan umum setoran seluruh santri dalam sesi halaqah ini.')
                        ->schema([
                            Forms\Components\Select::make('program')
                                ->label('Kelompok / Kelas Al-Qur\'an')
                                ->options([
                                    'tahsin'           => '📖 Kelas Tahsin (Kualitas & Kaidah Tajwid)',
                                    'yanbua'           => '📖 Kelas Yanbu\'a (Metode Baca Tulis Al-Qur\'an)',
                                    'tahfidz_dasar'    => '📖 Kelas Tahfidz Dasar (Juz 30 / Juz \'Amma)',
                                    'tahfidz_lanjutan' => '📖 Kelas Tahfidz Lanjutan (Juz 1-29)',
                                    'tahfidz'          => '📖 Kelas Tahfidz Al-Qur\'an (Umum)',
                                ])
                                ->default(function () {
                                    $user = Auth::user();
                                    if (!$user) return 'tahsin';
                                    if ($user->hasRole(User::ROLE_GURU_QURAN_TAHSIN)) return 'tahsin';
                                    if ($user->hasRole(User::ROLE_GURU_QURAN_YANBUA)) return 'yanbua';
                                    if ($user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_DASAR)) return 'tahfidz_dasar';
                                    if ($user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_LANJUTAN)) return 'tahfidz_lanjutan';
                                    return 'tahsin';
                                })
                                ->required()
                                ->live()
                                ->helperText('Pilih kelompok Al-Qur\'an untuk memuat santri binaan sesuai pembagian Waka Kurikulum.'),

                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal Setoran')
                                ->default(Carbon::now())
                                ->required(),

                            Forms\Components\Select::make('type')
                                ->label('Tipe Capaian')
                                ->options([
                                    'ziyadah'       => 'Ziyadah (Setoran Baru)',
                                    'murajaah'      => 'Muraja\'ah (Pengulangan)',
                                    'tasmi'         => 'Tasmi\' (Sekali Duduk)',
                                    'ujian_tahfidz' => 'Ujian Tahfidz / Mutqin',
                                ])
                                ->default('ziyadah')
                                ->required()
                                ->live(),

                            Forms\Components\Select::make('default_juz')
                                ->label('Juz Default')
                                ->options(array_combine(range(1, 30), range(1, 30)))
                                ->default(function () {
                                    $user = Auth::user();
                                    if ($user?->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_DASAR)) return 30;
                                    return 30;
                                })
                                ->live()
                                ->searchable(),

                            Forms\Components\Select::make('default_surah')
                                ->label('Surah Default')
                                ->options($surahs)
                                ->default('78')
                                ->searchable()
                                ->live(),

                            Forms\Components\TextInput::make('default_ayah')
                                ->label('Ayat / Jilid Halaman Default')
                                ->default('1-10')
                                ->placeholder('Contoh: 1-15 / Jilid 4 Hal. 10')
                                ->live(),
                        ])->columns(3),

                    Forms\Components\Section::make('👥 2. Lembar Setoran Hafalan Santri (Per Santri Sendiri-Sendiri)')
                        ->description('Format interaktif seperti Jurnal Mengajar. Atur surah, ayat, predikat kelulusan (Mumtaz, Jayyid Jiddan, Jayyid, Maqbul) dan catatan mutqin per santri.')
                        ->schema([
                            Forms\Components\ViewField::make('student_achievements')
                                ->view('filament.staff.forms.components.quran-achievement-batch-selector')
                                ->viewData(function (Forms\Get $get) use ($surahs) {
                                    $user = Auth::user();
                                    $program = $get('program');

                                    if (empty($program) && $user?->isQuranTeacher()) {
                                        $program = match (true) {
                                            $user->hasRole(User::ROLE_GURU_QURAN_TAHSIN) => 'tahsin',
                                            $user->hasRole(User::ROLE_GURU_QURAN_YANBUA) => 'yanbua',
                                            $user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_DASAR) => 'tahfidz_dasar',
                                            $user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_LANJUTAN) => 'tahfidz_lanjutan',
                                            default => null,
                                        };
                                    }

                                    $query = Student::query()->with(['classroom', 'guruQuran']);
                                    if (!empty($program)) {
                                        $query->where('kelas_quran', $program);
                                    } else {
                                        $query->whereRaw('1 = 0');
                                    }

                                    $students = $query->orderBy('name')->get();

                                    // Fallback jika belum ada santri di kelas_quran: ambil santri asuhan guru ini
                                    if ($students->isEmpty() && $user?->isQuranTeacher()) {
                                        $students = Student::where('guru_quran_id', $user->id)->with(['classroom', 'guruQuran'])->orderBy('name')->get();
                                    }

                                    return [
                                        'students'            => $students,
                                        'program'             => $program,
                                        'defaultJuz'          => $get('default_juz') ?? 30,
                                        'defaultSurah'        => $get('default_surah') ?? '78',
                                        'defaultAyah'         => $get('default_ayah') ?? '1-10',
                                        'defaultType'         => $get('type') ?? 'ziyadah',
                                        'defaultJilidHalaman' => $get('default_ayah') ?? '',
                                        'surahs'              => $surahs,
                                    ];
                                })
                                ->dehydrated(true)
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data) {
                    $program = $data['program'];
                    $tanggal = $data['tanggal'] ?? Carbon::now();
                    $type = $data['type'] ?? 'ziyadah';
                    $defaultJuz = $data['default_juz'] ?? 30;
                    $defaultSurah = $data['default_surah'] ?? '78';
                    $defaultAyah = $data['default_ayah'] ?? '1-10';
                    $studentAchievements = $data['student_achievements'] ?? [];
                    $teacherId = Auth::id();

                    if (empty($studentAchievements) || !is_array($studentAchievements)) {
                        Notification::make()
                            ->title('Gagal Menyimpan')
                            ->body('Lembar setoran belum terisi. Silakan pilih kelompok dan centang santri yang menyetor.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $savedCount = 0;

                    DB::transaction(function () use (
                        $studentAchievements,
                        $program,
                        $tanggal,
                        $type,
                        $defaultJuz,
                        $defaultSurah,
                        $defaultAyah,
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

                            $student = Student::find($studentId);
                            if (!$student) continue;

                            $instId = $student->institution_id ?: Auth::user()?->institution_id;
                            $juz = !empty($row['juz']) ? (int)$row['juz'] : (int)$defaultJuz;
                            $surah = !empty($row['surah']) ? (string)$row['surah'] : (string)$defaultSurah;
                            $ayah = !empty($row['ayah']) ? (string)$row['ayah'] : (string)$defaultAyah;
                            $predikat = !empty($row['predikat']) ? (string)$row['predikat'] : 'Jayyid Jiddan';
                            $catatan = $row['catatan'] ?? null;
                            $itemType = !empty($row['type']) ? (string)$row['type'] : $type;
                            $jilid = $row['jilid_halaman'] ?? null;

                            QuranAchievement::create([
                                'institution_id'    => $instId,
                                'student_id'        => $student->id,
                                'teacher_id'        => $teacherId,
                                'program'           => $program,
                                'type'              => [$itemType],
                                'juz'               => $juz,
                                'surah'             => $surah,
                                'ayah'              => $ayah,
                                'jilid_halaman'     => $jilid,
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
                            ->title('Setoran Hafalan Santri Berhasil Disimpan!')
                            ->body("Alhamdulillah, sebanyak {$savedCount} setoran hafalan santri telah tersimpan dan siap diverifikasi oleh Koordinator Madin.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Tidak Ada Setoran yang Disimpan')
                            ->body('Pastikan Anda mencentang setidaknya satu santri yang menyetor hari ini.')
                            ->warning()
                            ->send();
                    }
                }),

            Actions\Action::make('ke_skor_kualitas')
                ->label('⭐ Buka Skor Kualitas Bacaan & Tahsin')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->url(fn () => url('/staff/reading-quality-scores')),

            Actions\Action::make('peringkat_tahfidz')
                ->label('🏆 Lihat Peringkat Tahfidz')
                ->icon('heroicon-o-trophy')
                ->color('info')
                ->url(fn () => \App\Filament\Staff\Pages\TahfidzRanking::getUrl()),

            Actions\CreateAction::make()
                ->label('Input Setoran Satuan')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
