<?php

namespace App\Filament\Staff\Resources\GradeResource\Pages;

use App\Filament\Staff\Resources\GradeResource;
use App\Models\AcademicYear;
use App\Models\CapaianPembelajaran;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Rppm;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManageGrades extends ManageRecords
{
    protected static string $resource = GradeResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->has('open_rombel') || request()->has('open_batch')) {
            $this->defaultAction = 'input_nilai_rombel';
            $this->mountAction('input_nilai_rombel');
        }
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Nilai')
                ->icon('heroicon-o-list-bullet'),

            'perlu_remedial' => Tab::make('⚠️ Perlu Pembinaan (< 70)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('score', '<', 70)->where('is_remedial', false))
                ->badge(fn () => $this->getModel()::where('score', '<', 70)->where('is_remedial', false)->count())
                ->badgeColor('danger'),

            'remedial_selesai' => Tab::make('🔄 Remedial Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_remedial', true))
                ->badge(fn () => $this->getModel()::where('is_remedial', true)->count())
                ->badgeColor('warning'),

            'siap_pengayaan' => Tab::make('✅ Tuntas (Siap Pengayaan) (≥ 70)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where('score', '>=', 70)
                      ->orWhere(fn ($sq) => $sq->where('is_remedial', true)->where('nilai_remedial', '>=', 70));
                })->where('is_pengayaan', false))
                ->badge(fn () => $this->getModel()::where(function ($q) {
                    $q->where('score', '>=', 70)
                      ->orWhere(fn ($sq) => $sq->where('is_remedial', true)->where('nilai_remedial', '>=', 70));
                })->where('is_pengayaan', false)->count())
                ->badgeColor('success'),

            'pengayaan_selesai' => Tab::make('⭐ Pengayaan Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_pengayaan', true))
                ->badge(fn () => $this->getModel()::where('is_pengayaan', true)->count())
                ->badgeColor('warning'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('input_nilai_rombel')
                ->label('⚡ Input Nilai Rombel (Format Jurnal Mengajar)')
                ->modalHeading('📝 Input Nilai Siswa Sekelas Sekaligus (Format Jurnal Mengajar)')
                ->modalSubheading('Atur parameter pembelajaran (Mapel, CP, TP, Materi, Tipe Penilaian) satu kali di atas untuk seluruh kelas. Nilai dan catatan evaluasi diinput langsung per siswa di lembar tabel bawah.')
                ->color('primary')
                ->icon('heroicon-o-user-group')
                ->modalWidth('7xl')
                ->modalSubmitActionLabel('💾 Simpan Nilai Seluruh Siswa')
                ->form([
                    Forms\Components\Section::make('📚 1. Identitas Pembelajaran & Kurikulum (Satu untuk Seluruh Kelas)')
                        ->description('Pilihan di bawah ini berlaku untuk seluruh siswa di kelas/rombel yang dinilai. Guru cukup memilih CP, TP, dan Materi 1 kali.')
                        ->schema([
                            Forms\Components\Select::make('filter_kelas_quran')
                                ->label('Kelas / Kelompok Al-Qur\'an (Yanbu\'a / Tahsin / Tahfidz)')
                                ->options([
                                    'yanbua'           => '📖 Kelas Yanbu\'a (Metode Baca Tulis Al-Qur\'an)',
                                    'tahsin'           => '📖 Kelas Tahsin (Kualitas & Kaidah Tajwid)',
                                    'tahfidz_dasar'    => '📖 Kelas Tahfidz Dasar (Juz 30 / Juz \'Amma)',
                                    'tahfidz_lanjutan' => '📖 Kelas Tahfidz Lanjutan (Juz 1-29)',
                                ])
                                ->default(function () {
                                    $user = Auth::user();
                                    if (!$user) return null;
                                    if ($user->hasRole(User::ROLE_GURU_QURAN_TAHSIN)) return 'tahsin';
                                    if ($user->hasRole(User::ROLE_GURU_QURAN_YANBUA)) return 'yanbua';
                                    if ($user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_DASAR)) return 'tahfidz_dasar';
                                    if ($user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_LANJUTAN)) return 'tahfidz_lanjutan';
                                    return null;
                                })
                                ->placeholder('-- Pilih Kelas Al-Qur\'an (Jika Menilai Mapel Al-Qur\'an) --')
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    if (!empty($state)) {
                                        $set('classroom_id', null);
                                    }
                                })
                                ->columnSpan(fn () => (Auth::user()?->isQuranTeacher() ?? false) ? 3 : 1),

                            Forms\Components\Select::make('classroom_id')
                                ->label('Kelas Reguler / Rombel')
                                ->options(function () {
                                    $user = Auth::user();
                                    if (!$user) return [];
                                    $isPrivileged = $user->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah']);
                                    $isWaliKelas = $user->hasRole('wali_kelas');
                                    $query = Classroom::where('name', 'not like', '%tahsin%')
                                        ->where('name', 'not like', '%yanbua%')
                                        ->where('name', 'not like', '%yanbu\'a%')
                                        ->where('name', 'not like', '%tahfidz%')
                                        ->where('name', 'not like', '%qur%');
                                    if ($isPrivileged) {
                                        return $query->orderBy('name')->pluck('name', 'id');
                                    }
                                    $tscClassrooms = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('classroom_id')->toArray();
                                    if ($isWaliKelas) {
                                        $waliClassrooms = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
                                        $tscClassrooms = array_unique(array_merge($tscClassrooms, $waliClassrooms));
                                    }
                                    if ($user->isQuranTeacher()) {
                                        $quranClassrooms = Student::where('guru_quran_id', $user->id)->whereNotNull('classroom_id')->pluck('classroom_id')->toArray();
                                        $tscClassrooms = array_unique(array_merge($tscClassrooms, $quranClassrooms));
                                    }
                                    if (empty($tscClassrooms)) {
                                        return $query->orderBy('name')->pluck('name', 'id');
                                    }
                                    return $query->whereIn('id', $tscClassrooms)->orderBy('name')->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required(fn (Forms\Get $get) => empty($get('filter_kelas_quran')) && !(Auth::user()?->isQuranTeacher() ?? false))
                                ->helperText('Pilih kelas/rombel untuk memuat seluruh siswa di kelas tersebut.'),

                            Forms\Components\Select::make('subject_id')
                                ->label('Mata Pelajaran')
                                ->options(function () {
                                    $user = Auth::user();
                                    if (!$user) return [];
                                    $isPrivileged = $user->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah']);
                                    $isWaliKelas = $user->hasRole('wali_kelas');
                                    if ($isPrivileged || $isWaliKelas) {
                                        return Subject::orderBy('name')->pluck('name', 'id');
                                    }
                                    $subjectIds = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('subject_id')->toArray();
                                    if ($user->isQuranTeacher() || $user->hasRole('koordinator_madin')) {
                                        $quranSubjects = Subject::where(function ($sq) {
                                            $sq->where('name', 'like', '%qur%')
                                              ->orWhere('name', 'like', '%tahfidz%')
                                              ->orWhere('name', 'like', '%tahsin%')
                                              ->orWhere('name', 'like', '%yanbua%')
                                              ->orWhere('name', 'like', '%madin%')
                                              ->orWhere('name', 'like', '%diniyah%');
                                        })->pluck('id')->toArray();
                                        $subjectIds = array_unique(array_merge($subjectIds, $quranSubjects));
                                    }
                                    if (empty($subjectIds)) {
                                        return Subject::orderBy('name')->pluck('name', 'id');
                                    }
                                    return Subject::whereIn('id', $subjectIds)->orderBy('name')->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live(),

                            Forms\Components\Select::make('academic_year_id')
                                ->label('Tahun Ajaran')
                                ->options(fn () => AcademicYear::orderByDesc('id')->pluck('name', 'id'))
                                ->default(fn () => AcademicYear::where('is_active', true)->value('id') ?? AcademicYear::latest()->value('id'))
                                ->required(),

                            Forms\Components\Select::make('semester')
                                ->label('Semester')
                                ->options([
                                    'Ganjil' => 'Ganjil',
                                    'Genap'  => 'Genap',
                                ])
                                ->default('Ganjil')
                                ->required(),

                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal Penilaian')
                                ->default(now())
                                ->required(),

                            Forms\Components\Select::make('rppm_id')
                                ->label('Tarik dari Dokumen RPPM Guru (Opsional)')
                                ->options(function (Forms\Get $get) {
                                    $subjectId = $get('subject_id');
                                    $query = Rppm::query();
                                    if (!auth()->user()?->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah'])) {
                                        $query->where('guru_id', auth()->id());
                                    }
                                    if ($subjectId) {
                                        $subjectName = Subject::where('id', $subjectId)->value('name');
                                        if ($subjectName) {
                                            $query->where('mata_pelajaran', 'like', "%{$subjectName}%");
                                        }
                                    }
                                    return $query->latest()->get()->mapWithKeys(function ($r) {
                                        $tpCount = count($r->selected_tp_ids ?? []);
                                        return [$r->id => "RPPM: {$r->topik} ({$r->kelas_fase} | {$tpCount} TP)"];
                                    });
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if (!$state) return;
                                    $rppm = Rppm::find($state);
                                    if ($rppm) {
                                        if (!empty($rppm->topik) && empty($get('materi'))) {
                                            $set('materi', $rppm->topik);
                                        }
                                        if (!empty($rppm->capaian_pembelajaran_id)) {
                                            $set('capaian_pembelajaran_id', $rppm->capaian_pembelajaran_id);
                                        }
                                        if (!empty($rppm->selected_tp_ids)) {
                                            $set('tp_ids', $rppm->selected_tp_ids);
                                        }
                                    }
                                })
                                ->helperText('Pilih RPPM untuk otomatis mengisi CP, Materi Pokok, dan TP.')
                                ->columnSpan(fn () => 2),

                            Forms\Components\Select::make('capaian_pembelajaran_id')
                                ->label('Capaian Pembelajaran (CP) [Satu untuk Seluruh Siswa]')
                                ->options(function (Forms\Get $get) {
                                    $subjectId = $get('subject_id');
                                    $classroomId = $get('classroom_id');
                                    $query = CapaianPembelajaran::query();
                                    if ($subjectId) {
                                        $query->where('subject_id', $subjectId);
                                    }
                                    if ($classroomId) {
                                        $tingkat = Classroom::where('id', $classroomId)->value('tingkat');
                                        if ($tingkat) {
                                            $query->where('tingkat', $tingkat);
                                        }
                                    }
                                    return $query->latest()->get()->mapWithKeys(function ($cp) {
                                        return [$cp->id => $cp->display_title];
                                    });
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if (!$state) return;
                                    $cp = CapaianPembelajaran::find($state);
                                    if ($cp && empty($get('materi')) && !empty($cp->elemen)) {
                                        $set('materi', $cp->elemen);
                                    }
                                })
                                ->helperText('Pilih CP yang dinilai. Terisi otomatis jika memilih dokumen RPPM.'),

                            Forms\Components\TextInput::make('materi')
                                ->label('Materi Pokok yang Dinilai [Satu untuk Seluruh Siswa]')
                                ->placeholder('Contoh: Aljabar dan Persamaan Linier / Membaca Makharijul Huruf')
                                ->required()
                                ->helperText('Lingkup materi pokok yang diujikan dalam penilaian rombel ini.')
                                ->columnSpan(fn () => 2),

                            Forms\Components\Select::make('tp_ids')
                                ->label('Tujuan Pembelajaran (TP) Terkait [Multiple Choice - Satu untuk Seluruh Siswa]')
                                ->multiple()
                                ->options(function (Forms\Get $get) {
                                    $subjectId = $get('subject_id');
                                    $classroomId = $get('classroom_id');
                                    $rppmId = $get('rppm_id');
                                    $cpId = $get('capaian_pembelajaran_id');

                                    $rppmTpIds = [];
                                    if ($rppmId) {
                                        $rppm = Rppm::find($rppmId);
                                        $rppmTpIds = $rppm?->selected_tp_ids ?? [];
                                    }

                                    $query = TujuanPembelajaran::query();
                                    if ($subjectId) {
                                        $query->where('subject_id', $subjectId);
                                    }
                                    if ($cpId) {
                                        $query->where('capaian_pembelajaran_id', $cpId);
                                    } elseif ($classroomId) {
                                        $tingkat = Classroom::where('id', $classroomId)->value('tingkat');
                                        if ($tingkat) {
                                            $query->where('kelas_tingkat', $tingkat);
                                        }
                                    }

                                    $tps = $query->get();
                                    if ($tps->isEmpty() && $subjectId) {
                                        $tps = TujuanPembelajaran::where('subject_id', $subjectId)->get();
                                    }

                                    return $tps->mapWithKeys(function ($tp) use ($rppmTpIds) {
                                        $isRppm = in_array($tp->id, $rppmTpIds);
                                        $tag = $isRppm ? '📌 [Dari RPPM] ' : '';
                                        $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                        $kode = $tp->kode_tp ? "({$tp->kode_tp}) " : '';
                                        return [$tp->id => "{$tag}{$kode}{$prefix}" . Str::limit($tp->deskripsi_tp, 70)];
                                    });
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->helperText('💡 TP yang dipilih otomatis berlaku untuk seluruh siswa di lembar penilaian ini.')
                                ->columnSpanFull(),

                            Forms\Components\Select::make('type')
                                ->label('Tipe Nilai')
                                ->options([
                                    'formatif'     => '1. Nilai Formatif / Harian',
                                    'keterampilan' => '2. Keterampilan / Praktik',
                                    'sts'          => '3. STS (Sumatif Tengah Semester)',
                                    'sas'          => '4. SAS (Sumatif Akhir Semester)',
                                    'kustom'       => '5. Input Sendiri (Kustom)',
                                ])
                                ->default('formatif')
                                ->required()
                                ->live(),

                            Forms\Components\TextInput::make('custom_type_name')
                                ->label('Nama Tipe Nilai (Input Sendiri)')
                                ->placeholder('Contoh: Nilai Portofolio Kolaboratif')
                                ->visible(fn (Forms\Get $get) => $get('type') === 'kustom')
                                ->required(fn (Forms\Get $get) => $get('type') === 'kustom'),

                            Forms\Components\Select::make('bentuk_penilaian')
                                ->label('Bentuk Penilaian')
                                ->options([
                                    'kuis'           => 'Kuis / Quiz Singkat',
                                    'ulangan_harian' => 'Ulangan Harian',
                                    'presentasi'     => 'Presentasi / Unjuk Kerja',
                                    'kir'            => 'KIR (Karya Ilmiah Remaja)',
                                    'praktik'        => 'Praktik / Praktikum',
                                    'diskusi'        => 'Diskusi & Partisipasi Aktif',
                                    'kustom'         => 'Bentuk Lainnya (Input Sendiri)',
                                ])
                                ->default('ulangan_harian')
                                ->required()
                                ->live(),

                            Forms\Components\TextInput::make('custom_bentuk_penilaian')
                                ->label('Nama Bentuk Penilaian (Input Sendiri)')
                                ->placeholder('Contoh: Praktikum Lapangan Mandiri')
                                ->visible(fn (Forms\Get $get) => $get('bentuk_penilaian') === 'kustom')
                                ->required(fn (Forms\Get $get) => $get('bentuk_penilaian') === 'kustom'),
                        ])->columns(3),

                    Forms\Components\Section::make('👥 2. Lembar Nilai & Catatan Evaluasi Siswa (Per Siswa Sendiri-Sendiri)')
                        ->description('Format interaktif seperti Jurnal Mengajar. Masukkan nilai 0-100 dan catatan evaluasi per siswa. Manfaatkan fitur Template Nilai & Catatan Cepat untuk kemudahan pengisian sekelas.')
                        ->schema([
                            Forms\Components\ViewField::make('student_scores')
                                ->view('filament.staff.forms.components.student-grade-batch-selector')
                                ->viewData(function (Forms\Get $get) {
                                    $classroomId = $get('classroom_id');
                                    $filterQuran = $get('filter_kelas_quran');
                                    $user = Auth::user();

                                    if ($user?->isQuranTeacher() && empty($filterQuran)) {
                                        $filterQuran = match (true) {
                                            $user->hasRole(User::ROLE_GURU_QURAN_TAHSIN) => 'tahsin',
                                            $user->hasRole(User::ROLE_GURU_QURAN_YANBUA) => 'yanbua',
                                            $user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_DASAR) => 'tahfidz_dasar',
                                            $user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_LANJUTAN) => 'tahfidz_lanjutan',
                                            default => null,
                                        };
                                    }

                                    $query = Student::query()->with(['classroom', 'guruQuran']);
                                    if (!empty($filterQuran)) {
                                        $query->where('kelas_quran', $filterQuran);
                                    } elseif (!empty($classroomId)) {
                                        $query->where('classroom_id', $classroomId);
                                    } else {
                                        $query->whereRaw('1 = 0');
                                    }

                                    $students = $query->orderBy('name')->get();

                                    return [
                                        'students' => $students,
                                        'classroomId' => $classroomId,
                                        'filterKelasQuran' => $filterQuran,
                                    ];
                                })
                                ->dehydrated(true)
                                ->columnSpanFull(),
                        ]),
                ])
                ->action(function (array $data) {
                    $classroomId = $data['classroom_id'] ?? null;
                    $subjectId = $data['subject_id'] ?? null;
                    $academicYearId = $data['academic_year_id'] ?? null;
                    $semester = $data['semester'] ?? 'Ganjil';
                    $tanggal = $data['tanggal'] ?? now();
                    $rppmId = $data['rppm_id'] ?? null;
                    $cpId = $data['capaian_pembelajaran_id'] ?? null;
                    $materi = $data['materi'] ?? null;
                    $tpIds = $data['tp_ids'] ?? [];
                    $type = $data['type'] ?? 'formatif';
                    $customTypeName = $data['custom_type_name'] ?? null;
                    $bentukPenilaian = $data['bentuk_penilaian'] ?? 'ulangan_harian';
                    $customBentukPenilaian = $data['custom_bentuk_penilaian'] ?? null;
                    $studentScores = $data['student_scores'] ?? [];
                    $teacherId = Auth::id();

                    if (empty($studentScores) || !is_array($studentScores)) {
                        Notification::make()
                            ->title('Gagal Menyimpan')
                            ->body('Lembar nilai belum terisi. Silakan pilih kelas dan isi nilai siswa.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $savedCount = 0;

                    DB::transaction(function () use (
                        $studentScores,
                        $classroomId,
                        $subjectId,
                        $academicYearId,
                        $semester,
                        $tanggal,
                        $rppmId,
                        $cpId,
                        $materi,
                        $tpIds,
                        $type,
                        $customTypeName,
                        $bentukPenilaian,
                        $customBentukPenilaian,
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

                            $targetClassroomId = $classroomId ?: $student->classroom_id;
                            $instId = $student->institution_id ?: Auth::user()?->institution_id;

                            Grade::updateOrCreate(
                                [
                                    'student_id'       => $student->id,
                                    'subject_id'       => $subjectId,
                                    'academic_year_id' => $academicYearId,
                                    'semester'         => $semester,
                                    'type'             => $type,
                                    'tanggal'          => $tanggal,
                                    'materi'           => $materi,
                                ],
                                [
                                    'institution_id'          => $instId,
                                    'classroom_id'            => $targetClassroomId,
                                    'teacher_id'              => $teacherId,
                                    'rppm_id'                 => $rppmId,
                                    'capaian_pembelajaran_id' => $cpId,
                                    'tp_ids'                  => $tpIds,
                                    'score'                   => (float) $score,
                                    'catatan_guru'            => $catatan,
                                    'bentuk_penilaian'        => $bentukPenilaian,
                                    'custom_type_name'        => $customTypeName,
                                    'custom_bentuk_penilaian' => $customBentukPenilaian,
                                    'is_remedial'             => false,
                                    'is_pengayaan'            => false,
                                ]
                            );

                            $savedCount++;
                        }
                    });

                    if ($savedCount > 0) {
                        Notification::make()
                            ->title('Nilai Rombel Berhasil Disimpan!')
                            ->body("Alhamdulillah, sebanyak {$savedCount} nilai siswa telah berhasil tersimpan dengan parameter pembelajaran yang seragam.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Tidak Ada Nilai yang Disimpan')
                            ->body('Mohon masukkan skor nilai (0-100) setidaknya untuk satu orang siswa pada tabel.')
                            ->warning()
                            ->send();
                    }
                }),

            Actions\Action::make('rumah_nilai')
                ->label('🏠 Buka Lembar Input Nilai Per Mapel (Batch Grid)')
                ->color('success')
                ->icon('heroicon-o-academic-cap')
                ->url(fn () => url('/staff/input-nilai-mapel')),

            Actions\CreateAction::make()
                ->label('Input Nilai Satuan')
                ->icon('heroicon-o-plus-circle')
                ->mutateFormDataUsing(function (array $data): array {
                    if (empty($data['classroom_id']) && !empty($data['student_id'])) {
                        $student = \App\Models\Student::find($data['student_id']);
                        $data['classroom_id'] = $student?->classroom_id;
                    }
                    return $data;
                }),
        ];
    }
}
