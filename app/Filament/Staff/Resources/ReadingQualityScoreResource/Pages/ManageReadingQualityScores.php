<?php

namespace App\Filament\Staff\Resources\ReadingQualityScoreResource\Pages;

use App\Filament\Staff\Resources\ReadingQualityScoreResource;
use App\Models\Grade;
use App\Models\ReadingQualityScore;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageReadingQualityScores extends ManageRecords
{
    protected static string $resource = ReadingQualityScoreResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->has('open_rombel') || request()->has('open_batch')) {
            $this->defaultAction = 'input_skor_rombel';
            $this->mountAction('input_skor_rombel');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('input_skor_rombel')
                ->label('⚡ Input Skor Rombel Al-Qur\'an (Format Jurnal Mengajar)')
                ->modalHeading('⭐ Input Skor Kualitas Bacaan Santri Binaan (Format Jurnal Mengajar)')
                ->modalSubheading('Data kelas dan santri binaan otomatis ditentukan sesuai pembagian Waka Kurikulum (tanpa perlu memilih kelas). Masukkan skor (0-100) dan catatan evaluasi per santri.')
                ->color('primary')
                ->icon('heroicon-o-user-group')
                ->modalWidth('7xl')
                ->modalSubmitActionLabel('💾 Simpan Skor Seluruh Santri')
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
                                        <div class='flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200 font-bold text-xs'>
                                            <span class='text-base'>📖</span>
                                            <div>
                                                <div>Kelas {$label} • Dibina oleh: {$teacherName}</div>
                                                <div class='text-[10px] text-amber-600 dark:text-amber-400 font-normal'>Daftar santri di bawah otomatis dimuat dari penempatan Waka Kurikulum</div>
                                            </div>
                                        </div>
                                    ");
                                })
                                ->visible(fn () => (Auth::user()?->isQuranTeacher() && !Auth::user()?->hasAnyRole(['waka_kurikulum', 'superadmin'])) ?? true)
                                ->columnSpan(fn () => 2),

                            // Hidden input untuk menyimpan value program otomatis
                            Forms\Components\Hidden::make('program_otomatis')
                                ->default(fn () => Auth::user()?->getQuranSpecializationKey() ?? 'tahsin'),

                            // Dropdown HANYA untuk Waka Kurikulum / Superadmin jika ingin memilih kelas lain
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
                                ->default(Carbon::now())
                                ->required(),

                            Forms\Components\TextInput::make('jilid_halaman')
                                ->label('Materi Pokok / Jilid / Halaman / Juz Penilaian')
                                ->placeholder('Contoh: Yanbu\'a Jilid 3 Hal. 10-15 / Juz 30 Surah An-Naba')
                                ->helperText('Materi atau bab yang dinilai dalam pertemuan ini.')
                                ->columnSpan(fn () => 2),
                        ])->columns(4),

                    Forms\Components\Section::make('👥 2. Lembar Nilai Kualitas Bacaan Santri (Otomatis Termuat Seluruh Santri Binaan)')
                        ->description('Format interaktif seperti Jurnal Mengajar. Seluruh santri binaan sudah siap diinput skor kualitasnya (0-100) dan catatan evaluasi per santri.')
                        ->schema([
                            Forms\Components\ViewField::make('student_scores')
                                ->view('filament.staff.forms.components.quran-reading-score-batch-selector')
                                ->viewData(function (Forms\Get $get) {
                                    $user = Auth::user();
                                    
                                    // Deteksi program otomatis dari akun guru
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
                                        'students' => $students,
                                        'program'  => $program,
                                    ];
                                })
                                ->dehydrated(true)
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data) {
                    $program = $data['program'] ?? $data['program_otomatis'] ?? Auth::user()?->getQuranSpecializationKey() ?? 'tahsin';
                    $tanggal = $data['tanggal'] ?? Carbon::now();
                    $jilidHalaman = $data['jilid_halaman'] ?? null;
                    $studentScores = $data['student_scores'] ?? [];
                    $teacherId = Auth::id();

                    if (empty($studentScores) || !is_array($studentScores)) {
                        Notification::make()
                            ->title('Gagal Menyimpan')
                            ->body('Lembar skor belum terisi. Silakan isi skor kualitas santri.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $savedCount = 0;

                    DB::transaction(function () use (
                        $studentScores,
                        $program,
                        $tanggal,
                        $jilidHalaman,
                        $teacherId,
                        &$savedCount
                    ) {
                        foreach ($studentScores as $studentId => $entry) {
                            $score = is_array($entry) ? ($entry['score'] ?? null) : $entry;
                            $catatan = is_array($entry) ? ($entry['catatan'] ?? null) : null;

                            if ($score === null || $score === '' || !is_numeric($score)) {
                                continue;
                            }

                            $student = Student::find($studentId);
                            if (!$student) continue;

                            $instId = $student->institution_id ?: Auth::user()?->institution_id;
                            $scale = Grade::determineScale((float)$score);

                            ReadingQualityScore::create([
                                'institution_id'    => $instId,
                                'student_id'        => $student->id,
                                'teacher_id'        => $teacherId,
                                'program'           => $program,
                                'scale_score'       => (int) $score,
                                'jilid_halaman'     => $jilidHalaman,
                                'catatan_guru'      => $catatan,
                                'predikat'          => $scale['predikat'],
                                'tanggal'           => $tanggal,
                                'status_verifikasi' => 'Menunggu Verifikasi',
                            ]);

                            $savedCount++;
                        }
                    });

                    if ($savedCount > 0) {
                        Notification::make()
                            ->title('Skor Kualitas Bacaan Berhasil Disimpan!')
                            ->body("Alhamdulillah, sebanyak {$savedCount} skor santri binaan telah tersimpan otomatis dan siap diverifikasi oleh Koordinator Madin.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Tidak Ada Skor yang Disimpan')
                            ->body('Mohon isi skor kualitas (0-100) setidaknya untuk satu orang santri pada tabel.')
                            ->warning()
                            ->send();
                    }
                }),

            Actions\Action::make('ke_setoran_hafalan')
                ->label('📖 Buka Capaian & Setoran Hafalan')
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->url(fn () => url('/staff/quran-achievements')),

            Actions\CreateAction::make()
                ->label('Input Skor Satuan')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
