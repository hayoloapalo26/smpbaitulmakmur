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
                ->modalHeading('📖 Input Setoran Hafalan Santri Binaan (Format Jurnal Mengajar)')
                ->modalSubheading('Data kelas dan santri binaan otomatis ditentukan dari penempatan Waka Kurikulum (tanpa perlu memilih kelas). Atur setoran dan catatan evaluasi per santri di lembar bawah.')
                ->color('primary')
                ->icon('heroicon-o-user-group')
                ->modalWidth('7xl')
                ->modalSubmitActionLabel('💾 Simpan Setoran Seluruh Santri')
                ->form([
                    Forms\Components\Section::make('📖 1. Parameter Setoran Al-Qur\'an (Otomatis dari Waka Kurikulum)')
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
                                ->default(30)
                                ->live()
                                ->searchable(),

                            Forms\Components\Select::make('default_surah')
                                ->label('Surah Default')
                                ->options($surahs)
                                ->default('78')
                                ->searchable()
                                ->live(),

                            Forms\Components\TextInput::make('default_ayah')
                                ->label('Ayat / Jilid Default')
                                ->default('1-10')
                                ->placeholder('Contoh: 1-15 / Jilid 4 Hal. 10')
                                ->live(),
                        ])->columns(3),

                    Forms\Components\Section::make('👥 2. Lembar Setoran Hafalan Santri (Otomatis Termuat Seluruh Santri Binaan)')
                        ->description('Format interaktif seperti Jurnal Mengajar. Seluruh santri binaan sudah siap diinput setorannya. Atur surah, ayat, predikat kelulusan (Mumtaz, Jayyid Jiddan, Jayyid, Maqbul) dan catatan per santri.')
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
                                        $program = $get('program') ?? $get('program_otomatis') ?? 'tahsin';
                                    }

                                    $query = Student::query()->with(['classroom', 'guruQuran']);

                                    // Otomatisasi data santri binaan dari Waka Kurikulum
                                    if ($user?->isQuranTeacher() && !$user?->hasRole('superadmin')) {
                                        $query->where(function ($q) use ($user, $program) {
                                            $q->where('guru_quran_id', $user->id)
                                              ->orWhere('kelas_quran', $program);
                                        });
                                    } else {
                                        $query->where('kelas_quran', $program);
                                    }

                                    $students = $query->orderBy('name')->get();

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
                    $program = $data['program'] ?? $data['program_otomatis'] ?? Auth::user()?->getQuranSpecializationKey() ?? 'tahsin';
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
                            ->body('Lembar setoran belum terisi. Silakan centang santri yang menyetor.')
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
                            ->body("Alhamdulillah, sebanyak {$savedCount} setoran hafalan santri binaan telah tersimpan dan siap diverifikasi oleh Koordinator Madin.")
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
