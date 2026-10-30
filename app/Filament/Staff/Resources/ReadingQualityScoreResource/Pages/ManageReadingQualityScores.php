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
                ->modalHeading('⭐ Input Skor Kualitas Bacaan Sekelompok Santri (Format Jurnal Mengajar)')
                ->modalSubheading('Tentukan Kelompok Al-Qur\'an, Tanggal, dan Materi Jilid/Halaman satu kali di atas. Input skor kualitas bacaan (0-100) dan catatan evaluasi per santri di lembar bawah.')
                ->color('primary')
                ->icon('heroicon-o-user-group')
                ->modalWidth('7xl')
                ->modalSubmitActionLabel('💾 Simpan Skor Seluruh Santri')
                ->form([
                    Forms\Components\Section::make('📖 1. Parameter Kelompok Al-Qur\'an (Satu untuk Seluruh Santri)')
                        ->description('Pilihan di bawah berlaku untuk seluruh santri yang dinilai dalam sesi ini.')
                        ->schema([
                            Forms\Components\Select::make('program')
                                ->label('Kelompok / Kelas Al-Qur\'an')
                                ->options([
                                    'yanbua'           => '📖 Kelas Yanbu\'a (Metode Baca Tulis Al-Qur\'an)',
                                    'tahsin'           => '📖 Kelas Tahsin (Kualitas & Kaidah Tajwid)',
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
                                ->label('Tanggal Penilaian')
                                ->default(Carbon::now())
                                ->required(),

                            Forms\Components\TextInput::make('jilid_halaman')
                                ->label('Materi Pokok / Jilid / Halaman / Juz Penilaian')
                                ->placeholder('Contoh: Yanbu\'a Jilid 3 Hal. 10-15 / Juz 30 Surah An-Naba')
                                ->helperText('Materi atau bab yang dinilai dalam pertemuan ini.')
                                ->columnSpan(fn () => 2),
                        ])->columns(4),

                    Forms\Components\Section::make('👥 2. Lembar Nilai Kualitas Bacaan Santri (Per Santri Sendiri-Sendiri)')
                        ->description('Format interaktif seperti Jurnal Mengajar. Masukkan skor kualitas (0-100) dan catatan evaluasi per santri.')
                        ->schema([
                            Forms\Components\ViewField::make('student_scores')
                                ->view('filament.staff.forms.components.quran-reading-score-batch-selector')
                                ->viewData(function (Forms\Get $get) {
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
                                        'students' => $students,
                                        'program'  => $program,
                                    ];
                                })
                                ->dehydrated(true)
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data) {
                    $program = $data['program'];
                    $tanggal = $data['tanggal'] ?? Carbon::now();
                    $jilidHalaman = $data['jilid_halaman'] ?? null;
                    $studentScores = $data['student_scores'] ?? [];
                    $teacherId = Auth::id();

                    if (empty($studentScores) || !is_array($studentScores)) {
                        Notification::make()
                            ->title('Gagal Menyimpan')
                            ->body('Lembar skor belum terisi. Silakan pilih kelompok dan isi skor santri.')
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
                            ->body("Alhamdulillah, sebanyak {$savedCount} skor santri telah tersimpan dan siap diverifikasi oleh Koordinator Madin.")
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
