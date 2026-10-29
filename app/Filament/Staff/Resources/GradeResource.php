<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\GradeResource\Pages;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Rppm;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\TujuanPembelajaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GradeResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(array_merge([
            'guru_mapel',
            'koordinator_madin',
            'guru_kelas',
            'waka_kurikulum',
            'kepala_sekolah',
            'superadmin',
        ], \App\Models\User::QURAN_TEACHER_ROLES)) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(array_merge([
            'guru_mapel',
            'koordinator_madin',
            'guru_kelas',
            'wali_kelas',
            'waka_kurikulum',
            'kepala_sekolah',
            'superadmin',
        ], \App\Models\User::QURAN_TEACHER_ROLES)) ?? false;
    }

    protected static ?string $model = Grade::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationLabel = 'Input & Daftar Nilai Siswa';
    protected static ?string $modelLabel = 'Nilai Siswa';
    protected static ?string $pluralModelLabel = 'Daftar Nilai Siswa';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        if ($user->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah'])) {
            return $query;
        }

        // Koordinator Madin: Melihat nilai mapel Al-Qur'an / Diniyah atau nilai yang diinput oleh Guru Al-Qur'an
        if ($user->hasRole('koordinator_madin')) {
            return $query->where(function ($q) {
                $q->whereHas('subject', function ($sq) {
                    $sq->where('name', 'like', '%qur%')
                      ->orWhere('name', 'like', '%tahfidz%')
                      ->orWhere('name', 'like', '%tahsin%')
                      ->orWhere('name', 'like', '%yanbua%')
                      ->orWhere('name', 'like', '%madin%')
                      ->orWhere('name', 'like', '%diniyah%')
                      ->orWhere('name', 'like', '%pai%')
                      ->orWhere('name', 'like', '%islam%');
                })->orWhereHas('teacher', function ($tq) {
                    $tq->whereHas('roles', fn ($r) => $r->whereIn('name', \App\Models\User::QURAN_TEACHER_ROLES));
                });
            });
        }

        // Guru Mapel / Guru Kelas: Nilai pada mata pelajaran yang diampu
        $subjectIds = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('subject_id')->toArray();

        // Wali Kelas: Nilai siswa-siswi di kelas binaannya
        $waliClassroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
        $waliStudentIds = Student::whereIn('classroom_id', $waliClassroomIds)->pluck('id')->toArray();

        // Guru Al-Qur'an: Nilai siswa-siswi yang ditempatkan di bawah binaannya
        $quranStudentIds = [];
        if ($user->isQuranTeacher()) {
            $quranStudentIds = Student::where('guru_quran_id', $user->id)->pluck('id')->toArray();
        }

        return $query->where(function ($q) use ($user, $subjectIds, $waliStudentIds, $quranStudentIds) {
            $hasCondition = false;
            if (!empty($subjectIds)) {
                $q->whereIn('subject_id', $subjectIds);
                $hasCondition = true;
            }
            if (!empty($waliStudentIds)) {
                if ($hasCondition) {
                    $q->orWhereIn('student_id', $waliStudentIds);
                } else {
                    $q->whereIn('student_id', $waliStudentIds);
                }
                $hasCondition = true;
            }
            if (!empty($quranStudentIds)) {
                if ($hasCondition) {
                    $q->orWhereIn('student_id', $quranStudentIds);
                } else {
                    $q->whereIn('student_id', $quranStudentIds);
                }
                $hasCondition = true;
            }
            if ($hasCondition) {
                $q->orWhere('teacher_id', $user->id);
            } else {
                $q->where('teacher_id', $user->id);
            }
        });
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $studentIds = [];
        $subjectIds = [];

        $isPrivileged = $user->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah']);
        $isWaliKelas = $user->hasRole('wali_kelas');

        if (!$isPrivileged) {
            $tsc = TeacherSubjectClass::where('teacher_id', $user->id)->get();
            $subjectIds = $tsc->pluck('subject_id')->toArray();
            $classroomIds = $tsc->pluck('classroom_id')->toArray();

            if ($isWaliKelas) {
                $waliClassrooms = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
                $classroomIds = array_unique(array_merge($classroomIds, $waliClassrooms));
            }

            if ($user->isQuranTeacher()) {
                $quranClassrooms = Student::where('guru_quran_id', $user->id)->whereNotNull('classroom_id')->pluck('classroom_id')->toArray();
                $classroomIds = array_unique(array_merge($classroomIds, $quranClassrooms));
            }

            $studentIds = Student::whereIn('classroom_id', $classroomIds)->pluck('id')->toArray();

            if ($user->isQuranTeacher()) {
                $quranStudents = Student::where('guru_quran_id', $user->id)->pluck('id')->toArray();
                $studentIds = array_unique(array_merge($studentIds, $quranStudents));
            }
        }

        return $form
            ->schema([
                // 1. IDENTITAS KELAS, SISWA & PERIODE PEMBELAJARAN
                Forms\Components\Section::make('📚 Identitas Pembelajaran & Siswa')
                    ->description('Pilih kelas terlebih dahulu, kemudian daftar nama siswa akan otomatis dimuat sesuai kelas yang dipilih.')
                    ->schema([
                        Forms\Components\Hidden::make('teacher_id')
                            ->default(fn () => Auth::id()),

                        Forms\Components\Select::make('filter_kelas_quran')
                            ->label('1. Filter Kelas Al-Qur\'an (Khusus Nilai Tahsin / Yanbu\'a / Tahfidz)')
                            ->options([
                                'tahsin'           => '📖 Kelas Tahsin (Kualitas & Kaidah Tajwid)',
                                'yanbua'           => '📖 Kelas Yanbu\'a (Metode Baca Tulis Al-Qur\'an)',
                                'tahfidz_dasar'    => '📖 Kelas Tahfidz Dasar (Juz 30 / Juz \'Amma)',
                                'tahfidz_lanjutan' => '📖 Kelas Tahfidz Lanjutan (Juz 1-29)',
                            ])
                            ->placeholder('-- Semua / Pilih Kelas Al-Qur\'an --')
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('student_id', null);
                            })
                            ->helperText('Pilih untuk langsung menyaring siswa yang ditempatkan di kelas Al-Qur\'an ini.'),

                        Forms\Components\Select::make('classroom_id')
                            ->label('2. Kelas Reguler / Rombel')
                            ->options(function () use ($isPrivileged, $isWaliKelas) {
                                $user = Auth::user();
                                if (!$user) return [];
                                if ($isPrivileged) {
                                    return Classroom::orderBy('name')->pluck('name', 'id');
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
                                return Classroom::whereIn('id', $tscClassrooms)->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('student_id', null);
                            })
                            ->helperText('Pilih rombel jika ingin menyaring siswa per rombel reguler.'),

                        Forms\Components\Select::make('student_id')
                            ->label('3. Nama Siswa (Lengkap Dengan Status Kelas Al-Qur\'an)')
                            ->options(function (Forms\Get $get) {
                                $user = Auth::user();
                                if (!$user) return [];
                                $classroomId = $get('classroom_id');
                                $quranClass = $get('filter_kelas_quran');

                                $query = Student::query()->with(['classroom', 'guruQuran']);

                                // 1. Isolasi Lembaga jika bukan superadmin
                                if ($user->institution_id && $user->institution_id != 1 && !$user->hasRole('superadmin')) {
                                    if (\Illuminate\Support\Facades\Schema::hasColumn('students', 'institution_id')) {
                                        $query->where('students.institution_id', $user->institution_id);
                                    } else {
                                        $query->whereHas('classroom', fn ($cq) => $cq->where('institution_id', $user->institution_id));
                                    }
                                }

                                // 2. Filter berdasarkan Kelas Al-Qur'an jika dipilih
                                if ($quranClass) {
                                    $query->where('kelas_quran', $quranClass);
                                    if ($user->isQuranTeacher()) {
                                        $hasAssignedToMe = (clone $query)->where('guru_quran_id', $user->id)->exists();
                                        if ($hasAssignedToMe) {
                                            $query->where('guru_quran_id', $user->id);
                                        }
                                    }
                                } elseif ($user->isQuranTeacher()) {
                                    $spec = match (true) {
                                        $user->hasRole(User::ROLE_GURU_QURAN_YANBUA) => 'yanbua',
                                        $user->hasRole(User::ROLE_GURU_QURAN_TAHSIN) => 'tahsin',
                                        $user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_DASAR) => 'tahfidz_dasar',
                                        $user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_LANJUTAN) => 'tahfidz_lanjutan',
                                        default => null,
                                    };
                                    $query->where(function ($sq) use ($user, $spec) {
                                        $sq->where('guru_quran_id', $user->id);
                                        if ($spec) {
                                            $sq->orWhere('kelas_quran', $spec);
                                        }
                                    });
                                }

                                // 3. Filter rombel reguler jika dipilih
                                if ($classroomId) {
                                    $query->where('classroom_id', $classroomId);
                                }

                                return $query->orderBy('name')->get()->mapWithKeys(function ($s) {
                                    $quranBadge = $s->kelas_quran_label 
                                        ? " • [Kelas Al-Qur'an: {$s->kelas_quran_label}]" 
                                        : " • [⚠️ Belum Ada Kelas Al-Qur'an]";
                                    $guru = $s->guruQuran?->name ? " (Guru: {$s->guruQuran->name})" : '';
                                    $rombel = $s->classroom?->name ? " [Rombel: {$s->classroom->name}]" : '';
                                    $nis = $s->nis ? " (NIS: {$s->nis})" : '';
                                    return [
                                        $s->id => "{$s->name}{$nis}{$quranBadge}{$guru}{$rombel}"
                                    ];
                                });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $student = Student::with(['classroom', 'guruQuran'])->find($state);
                                    if ($student?->classroom_id) {
                                        $set('classroom_id', $student->classroom_id);
                                    }
                                    if ($student?->kelas_quran) {
                                        $set('filter_kelas_quran', $student->kelas_quran);
                                    }
                                }
                            })
                            ->helperText('Daftar siswa otomatis menampilkan status penempatan kelas Al-Qur\'an masing-masing siswa.'),

                        Forms\Components\Select::make('subject_id')
                            ->label('3. Mata Pelajaran')
                            ->options(function () use ($isPrivileged, $isWaliKelas, $subjectIds) {
                                if ($isPrivileged || $isWaliKelas) {
                                    return Subject::orderBy('name')->pluck('name', 'id');
                                }
                                return Subject::whereIn('id', $subjectIds)->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('academic_year_id')
                            ->relationship('academicYear', 'name')
                            ->label('4. Tahun Ajaran')
                            ->default(fn () => AcademicYear::where('is_active', true)->value('id') ?? AcademicYear::latest()->value('id'))
                            ->required(),

                        Forms\Components\Select::make('semester')
                            ->label('5. Semester')
                            ->options([
                                'Ganjil' => 'Ganjil',
                                'Genap'  => 'Genap',
                            ])
                            ->default('Ganjil')
                            ->required(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('6. Tanggal Penilaian')
                            ->default(now())
                            ->required()
                            ->helperText('Tanggal saat penilaian dilaksanakan / diinput oleh guru.'),
                    ])->columns(3),

                // 2. PENARIKAN TP OTOMATIS DARI RPPM & LINGKUP MATERI
                Forms\Components\Section::make('🎯 Lingkup Materi & Penarikan TP dari RPPM')
                    ->description('Tarik otomatis Tujuan Pembelajaran (TP) dari dokumen RPPM atau pilih manual dari TP yang telah disusun.')
                    ->schema([
                        Forms\Components\Select::make('rppm_id')
                            ->label('Tarik Otomatis dari Dokumen RPPM Guru (Opsional)')
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
                                    if (!empty($rppm->selected_tp_ids)) {
                                        $set('tp_ids', $rppm->selected_tp_ids);
                                    }
                                }
                            })
                            ->helperText('Pilih RPPM untuk otomatis mengisi Materi Pokok dan memilih Tujuan Pembelajaran yang sesuai.'),

                        Forms\Components\TextInput::make('materi')
                            ->label('Materi Pokok yang Dinilai')
                            ->placeholder('Contoh: Operasi Aljabar dan Persamaan Linier Satu Variabel / Membaca Gambar Kerja')
                            ->required()
                            ->helperText('Lingkup materi pokok yang diujikan dalam penilaian ini.'),

                        Forms\Components\Select::make('tp_ids')
                            ->label('Tujuan Pembelajaran (TP) Terkait [Multiple Choice]')
                            ->multiple()
                            ->options(function (Forms\Get $get) {
                                $subjectId = $get('subject_id');
                                $classroomId = $get('classroom_id');
                                $rppmId = $get('rppm_id');

                                $rppmTpIds = [];
                                if ($rppmId) {
                                    $rppm = Rppm::find($rppmId);
                                    $rppmTpIds = $rppm?->selected_tp_ids ?? [];
                                }

                                $query = TujuanPembelajaran::query();
                                if ($subjectId) {
                                    $query->where('subject_id', $subjectId);
                                }
                                if ($classroomId) {
                                    $tingkat = Classroom::where('id', $classroomId)->value('tingkat');
                                    if ($tingkat) {
                                        $query->where('kelas_tingkat', $tingkat);
                                    }
                                }

                                return $query->get()->mapWithKeys(function ($tp) use ($rppmTpIds) {
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
                            ->helperText('💡 Fitur Multiple Choice: Anda dapat memilih lebih dari satu TP sekaligus (misal TP 1 dan TP 2 dinilai bersamaan dengan satu skor nilai).')
                            ->columnSpanFull(),
                    ])->columns(2),

                // 3. PARAMETER PENILAIAN, SKOR & CATATAN GURU PER-TP
                Forms\Components\Section::make('📝 Bentuk Penilaian, Skor & Evaluasi Umpan Balik')
                    ->description('Skala Nilai Resmi: [100: Excellent] | [90-99: Sangat Baik] | [80-89: Baik] | [70-79: Cukup Baik] | [< 70: Perlu Pembinaan].')
                    ->schema([
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
                            ->placeholder('Contoh: Nilai Portofolio Kolaboratif / Proyek P5')
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

                        Forms\Components\TextInput::make('score')
                            ->label('Nilai Siswa (0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->prefix('Skor:')
                            ->live()
                            ->helperText(function (Forms\Get $get) {
                                $score = $get('score');
                                if ($score === null || $score === '') {
                                    return '💡 Skala Nilai: 100 (Excellent) | 90 - 99 (Sangat Baik) | 80 - 89 (Baik) | 70 - 79 (Cukup Baik) | < 70 (Perlu Pembinaan).';
                                }
                                $scale = Grade::determineScale((float)$score);
                                if ($scale['is_tuntas']) {
                                    return "✅ Predikat: {$scale['label']} (Tuntas - Memenuhi Kriteria & Berhak mengikuti Pengayaan).";
                                }
                                return "⚠️ Predikat: {$scale['label']} (Belum Tuntas - Perlu Pembinaan / Mengikuti Remedial).";
                            })
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('catatan_per_tp')
                            ->label('Catatan Guru per-TP (Contoh: "Masih keliru membaca gambar kerja", "Sangat terampil menggunakan alat")')
                            ->keyLabel('ID / Kode TP')
                            ->valueLabel('Catatan Umpan Balik Guru per TP')
                            ->helperText('Catatan spesifik capaian indikator per TP yang diujikan.')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('catatan_guru')
                            ->label('Rangkuman Catatan Guru / Evaluasi untuk Rapor (Wajib)')
                            ->placeholder('Contoh: Ananda sangat terampil menggunakan alat praktikum, namun masih keliru membaca gambar kerja secara mandiri.')
                            ->rows(3)
                            ->required()
                            ->helperText('Wajib diisi sebagai catatan evaluasi guru untuk perkembangan belajar siswa.')
                            ->columnSpanFull(),
                    ])->columns(2),

                // 4. BAGIAN COLLAPSIBLE TINDAK LANJUT: REMEDIAL
                Forms\Components\Section::make('🔄 Fitur Tindak Lanjut: Remedial (Perbaikan Nilai)')
                    ->description('Dapat diisi jika siswa belum tuntas pada TP yang diuji dan telah melaksanakan remedial.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('is_remedial')
                            ->label('Aktifkan Status Telah Melaksanakan Remedial')
                            ->live(),

                        Forms\Components\DatePicker::make('tanggal_remedial')
                            ->label('Tanggal Pelaksanaan Remedial')
                            ->default(now()),

                        Forms\Components\TextInput::make('nilai_remedial')
                            ->label('Nilai Ulang Remedial (0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->prefix('Nilai Ulang:'),

                        Forms\Components\Select::make('tp_remedial_ids')
                            ->label('TP yang Diremedial')
                            ->multiple()
                            ->options(function (Forms\Get $get) {
                                $tpIds = $get('tp_ids') ?? [];
                                return TujuanPembelajaran::whereIn('id', $tpIds)->get()->mapWithKeys(function ($tp) {
                                    return [$tp->id => "({$tp->kode_tp}) " . Str::limit($tp->deskripsi_tp, 50)];
                                });
                            }),

                        Forms\Components\Textarea::make('catatan_remedial')
                            ->label('Catatan Guru Remedial & Bentuk Perbaikan')
                            ->placeholder('Contoh: Diberikan penjelasan ulang konsep membaca gambar kerja dan penugasan soal latihan terbimbing.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                // 5. BAGIAN COLLAPSIBLE TINDAK LANJUT: PENGAYAAN
                Forms\Components\Section::make('⭐ Fitur Tindak Lanjut: Pengayaan (Upgrade TP)')
                    ->description('Khusus bagi siswa yang nilai TP-nya sudah tuntas. Berikan bentuk penugasan pengayaan dan rumusan upgrade TP.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('is_pengayaan')
                            ->label('Aktifkan Status Telah Melaksanakan Pengayaan')
                            ->live(),

                        Forms\Components\Select::make('tp_pengayaan_id')
                            ->label('TP yang Dikembangkan (Wajib sebagai Tautan ke Rapor)')
                            ->options(function (Forms\Get $get) {
                                $tpIds = $get('tp_ids') ?? [];
                                return TujuanPembelajaran::whereIn('id', $tpIds)->get()->mapWithKeys(function ($tp) {
                                    return [$tp->id => "({$tp->kode_tp}) " . Str::limit($tp->deskripsi_tp, 60)];
                                });
                            }),

                        Forms\Components\Select::make('bentuk_pengayaan')
                            ->label('Bentuk Pengayaan')
                            ->options([
                                'soal_tingkat_lanjut' => 'Soal Tingkat Lanjut (HOTS)',
                                'projek_mini'        => 'Projek Mini',
                                'tutor_sebaya'        => 'Tutor Sebaya',
                                'studi_kasus'         => 'Studi Kasus',
                                'praktik_lanjutan'    => 'Praktik Lanjutan',
                                'kustom'              => 'Bentuk Lainnya (Input Sendiri)',
                            ])
                            ->default('projek_mini')
                            ->live(),

                        Forms\Components\TextInput::make('custom_bentuk_pengayaan')
                            ->label('Nama Bentuk Pengayaan (Input Sendiri)')
                            ->visible(fn (Forms\Get $get) => $get('bentuk_pengayaan') === 'kustom'),

                        Forms\Components\TextInput::make('link_drive_pengayaan')
                            ->label('Link Google Drive Berkas Pengayaan (Upload Drive)')
                            ->placeholder('https://drive.google.com/file/d/.../view')
                            ->url()
                            ->prefixIcon('heroicon-o-link'),

                        Forms\Components\DatePicker::make('tanggal_pengayaan')
                            ->label('Tanggal Pelaksanaan Pengayaan')
                            ->default(now()),

                        Forms\Components\TextInput::make('nilai_pengayaan')
                            ->label('Nilai Pengayaan (0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->prefix('Skor:'),

                        Forms\Components\Textarea::make('upgraded_tp_description')
                            ->label('Upgrade TP Pengayaan (Manual / Rumusan Baru untuk Rapor)')
                            ->placeholder('Contoh: Mampu merancang prosedur kerja mandiri dan menganalisis studi kasus nyata secara komprehensif.')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('catatan_pengayaan')
                            ->label('Catatan Guru Pengayaan (Wajib isi, misal: "Mampu merancang prosedur kerja sendiri")')
                            ->placeholder('Contoh: Mampu merancang prosedur kerja sendiri dan sangat aktif membantu rekan sebagai tutor sebaya.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Siswa')
                    ->weight('bold')
                    ->description(fn (Grade $record) => 'NIS: ' . ($record->student?->nis ?? '-')),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe Nilai')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'formatif'     => 'primary',
                        'keterampilan' => 'warning',
                        'sts'          => 'info',
                        'sas'          => 'success',
                        default        => 'purple',
                    })
                    ->formatStateUsing(function (string $state, Grade $record): string {
                        if ($state === 'kustom' && $record->custom_type_name) {
                            return 'Kustom: ' . $record->custom_type_name;
                        }
                        return match ($state) {
                            'formatif'     => 'Formatif / Harian',
                            'keterampilan' => 'Keterampilan / Praktik',
                            'sts'          => 'STS (Tengah Sem.)',
                            'sas'          => 'SAS (Akhir Sem.)',
                            default        => ucfirst($state),
                        };
                    }),

                Tables\Columns\TextColumn::make('bentuk_penilaian')
                    ->label('Bentuk')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state, Grade $record) => $state === 'kustom' ? ($record->custom_bentuk_penilaian ?: 'Kustom') : ucfirst(str_replace('_', ' ', (string)$state))),

                Tables\Columns\TextColumn::make('nilai_akhir')
                    ->label('Nilai Akhir')
                    ->weight('bold')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('score', $direction))
                    ->color(function (Grade $record): string {
                        if ($record->is_pengayaan) return 'warning'; // Emas Pengayaan
                        if ($record->isTuntas()) return 'success';
                        return 'danger';
                    })
                    ->formatStateUsing(function (Grade $record): string {
                        if ($record->is_remedial && $record->nilai_remedial !== null) {
                            return "{$record->nilai_remedial} (Awal: {$record->score})";
                        }
                        return (string)$record->score;
                    })
                    ->description(function (Grade $record) {
                        if ($record->is_pengayaan) {
                            return '⭐ Pengayaan Selesai (' . ($record->nilai_pengayaan ?? $record->score) . ')';
                        }
                        if ($record->is_remedial) {
                            return '🔄 Remedial: ' . ($record->nilai_remedial >= 70 ? 'Tuntas' : 'Perlu Pembinaan');
                        }
                        return $record->isTuntas() ? "✅ Tuntas ({$record->predikat})" : '⚠️ Perlu Pembinaan';
                    }),

                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat Skala')
                    ->badge()
                    ->color(fn (Grade $record): string => $record->predikat_color)
                    ->formatStateUsing(fn (Grade $record): string => $record->predikat)
                    ->description(fn (Grade $record): string => $record->predikat_label),

                Tables\Columns\TextColumn::make('tp_count')
                    ->label('TP Diuji')
                    ->state(fn (Grade $record) => count($record->tp_ids ?? []) . ' TP')
                    ->badge()
                    ->color('primary')
                    ->tooltip(function (Grade $record) {
                        $tps = $record->selected_tps;
                        if ($tps->isEmpty()) return 'Tidak ada TP terdata';
                        return $tps->map(fn ($t) => "• [{$t->kode_tp}] {$t->deskripsi_tp}")->join("\n");
                    }),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tgl Penilaian')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('catatan_guru')
                    ->label('Catatan Guru')
                    ->limit(35)
                    ->wrap()
                    ->tooltip(fn (Grade $record) => $record->catatan_guru),

                Tables\Columns\TextColumn::make('status_tindak_lanjut')
                    ->label('Tindak Lanjut')
                    ->badge()
                    ->color(fn (Grade $record) => match (true) {
                        $record->is_pengayaan => 'warning',
                        $record->is_remedial => 'info',
                        $record->isTuntas() => 'success',
                        default => 'danger',
                    })
                    ->state(function (Grade $record): string {
                        if ($record->is_pengayaan) return '⭐ Pengayaan';
                        if ($record->is_remedial) return '🔄 Remedial';
                        if ($record->isTuntas()) return "✅ Tuntas ({$record->predikat})";
                        return '⚠️ Perlu Pembinaan';
                    }),

                Tables\Columns\TextColumn::make('status_verifikasi')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Diterima' => 'success',
                        'Perlu Perbaikan' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->options([
                        'Menunggu Verifikasi' => 'Menunggu Verifikasi',
                        'Diterima' => 'Diterima',
                        'Perlu Perbaikan' => 'Perlu Perbaikan',
                    ]),

                Tables\Filters\SelectFilter::make('skala_nilai')
                    ->label('Filter Predikat Skala Nilai')
                    ->options([
                        'excellent'       => '100 : Excellent',
                        'sangat_baik'     => '90 - 99 : Sangat Baik',
                        'baik'            => '80 - 89 : Baik',
                        'cukup_baik'      => '70 - 79 : Cukup Baik',
                        'perlu_pembinaan' => '< 70 : Perlu Pembinaan',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) return $query;
                        return match ($data['value']) {
                            'excellent'       => $query->where('score', '>=', 100),
                            'sangat_baik'     => $query->whereBetween('score', [90, 99.99]),
                            'baik'            => $query->whereBetween('score', [80, 89.99]),
                            'cukup_baik'      => $query->whereBetween('score', [70, 79.99]),
                            'perlu_pembinaan' => $query->where('score', '<', 70),
                            default           => $query,
                        };
                    }),

                Tables\Filters\SelectFilter::make('classroom_id')
                    ->relationship('classroom', 'name')
                    ->label('Filter Kelas')
                    ->preload(),

                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name')
                    ->label('Filter Mapel')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Filter Tipe Nilai')
                    ->options([
                        'formatif'     => 'Formatif / Harian',
                        'keterampilan' => 'Keterampilan / Praktik',
                        'sts'          => 'STS',
                        'sas'          => 'SAS',
                        'kustom'       => 'Kustom',
                    ]),

                Tables\Filters\Filter::make('perlu_remedial')
                    ->label('Hanya Perlu Pembinaan / Remedial (< 70)')
                    ->query(fn (Builder $query): Builder => $query->where('score', '<', 70)->where('is_remedial', false)),

                Tables\Filters\Filter::make('tuntas_pengayaan')
                    ->label('Hanya Tuntas / Siap Pengayaan (>= 70)')
                    ->query(fn (Builder $query): Builder => $query->where('score', '>=', 70)->orWhere(fn ($q) => $q->where('is_remedial', true)->where('nilai_remedial', '>=', 70))),
            ])
            ->actions([
                Tables\Actions\Action::make('terima_nilai')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Grade $record) => $record->status_verifikasi !== 'Diterima' && (auth()->user()?->hasAnyRole(['koordinator_madin', 'kepala_sekolah', 'waka_kurikulum', 'superadmin']) ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Terima & Verifikasi Nilai Siswa')
                    ->modalDescription('Apakah Anda yakin menerima dan memvalidasi nilai yang diinputkan guru ini?')
                    ->action(function (Grade $record) {
                        $record->update([
                            'status_verifikasi' => 'Diterima',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Nilai Berhasil Diterima & Diverifikasi')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // 🔄 FITUR REMEDIAL: Inputan tanggal dan nilai ulang untuk TP yang belum tuntas
                Tables\Actions\Action::make('remedial')
                    ->label('Remedial')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalHeading(fn (Grade $record) => "🔄 Remedial / Perbaikan Nilai: {$record->student?->name} ({$record->subject?->name})")
                    ->modalDescription('Inputkan tanggal dan nilai ulang bagi siswa pada Tujuan Pembelajaran (TP) yang belum tuntas.')
                    ->modalSubmitActionLabel('Simpan Nilai Remedial')
                    ->form([
                        Forms\Components\Placeholder::make('info_nilai_awal')
                            ->label('Informasi Nilai Awal')
                            ->content(fn (Grade $record) => "Nilai Awal: {$record->score} | Skala: {$record->predikat_label} | Batas Ketuntasan: 70 | Status: " . ($record->isTuntas() ? 'Tuntas' : 'Perlu Pembinaan')),

                        Forms\Components\Select::make('tp_remedial_ids')
                            ->label('1. TP yang Belum Tuntas / Diremedial (Multiple Choice)')
                            ->multiple()
                            ->options(function (Grade $record) {
                                $tpIds = $record->tp_ids ?? [];
                                return TujuanPembelajaran::whereIn('id', $tpIds)->get()->mapWithKeys(function ($tp) {
                                    $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                    return [$tp->id => "({$tp->kode_tp}) {$prefix}" . Str::limit($tp->deskripsi_tp, 65)];
                                });
                            })
                            ->default(fn (Grade $record) => $record->tp_remedial_ids ?? $record->tp_ids)
                            ->required()
                            ->helperText('Pilih TP mana yang diremedial oleh siswa ini.'),

                        Forms\Components\DatePicker::make('tanggal_remedial')
                            ->label('2. Tanggal Pelaksanaan Remedial')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('nilai_remedial')
                            ->label('3. Nilai Ulang Remedial (0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('Contoh: 78')
                            ->prefix('Nilai Ulang:')
                            ->required(),

                        Forms\Components\Textarea::make('catatan_remedial')
                            ->label('4. Catatan Guru Remedial & Bentuk Perbaikan (Wajib)')
                            ->placeholder('Contoh: Siswa telah diberikan penjelasan ulang konsep membaca gambar kerja dan penugasan soal terbimbing dengan hasil tuntas.')
                            ->rows(3)
                            ->required()
                            ->helperText('Wajib diisi sebagai bukti evaluasi tindak lanjut penuntasan TP.')
                            ->columnSpanFull(),
                    ])
                    ->fillForm(fn (Grade $record) => [
                        'tp_remedial_ids'  => $record->tp_remedial_ids ?? $record->tp_ids,
                        'tanggal_remedial' => $record->tanggal_remedial ?? now(),
                        'nilai_remedial'   => $record->nilai_remedial,
                        'catatan_remedial' => $record->catatan_remedial,
                    ])
                    ->action(function (Grade $record, array $data) {
                        $record->update([
                            'is_remedial'      => true,
                            'tp_remedial_ids'  => $data['tp_remedial_ids'],
                            'tanggal_remedial' => $data['tanggal_remedial'],
                            'nilai_remedial'   => $data['nilai_remedial'],
                            'catatan_remedial' => $data['catatan_remedial'],
                        ]);

                        Notification::make()
                            ->title('Nilai Remedial Berhasil Disimpan')
                            ->body("Data perbaikan nilai untuk {$record->student?->name} berhasil diperbarui.")
                            ->success()
                            ->send();
                    }),

                // ⭐ FITUR PENGAYAAN: Khusus siswa yang nilai TP-nya sudah tuntas
                Tables\Actions\Action::make('pengayaan')
                    ->label('Pengayaan')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->visible(fn (Grade $record) => $record->isTuntas())
                    ->modalHeading(fn (Grade $record) => "⭐ Program Pengayaan & Upgrade TP: {$record->student?->name} ({$record->subject?->name})")
                    ->modalDescription('Siswa telah mencapai nilai tuntas pada TP ini. Lengkapi bentuk pengayaan, link upload drive, dan upgrade rumusan TP untuk rapor.')
                    ->modalSubmitActionLabel('Simpan Program Pengayaan')
                    ->form([
                        Forms\Components\Placeholder::make('info_siswa_tuntas')
                            ->label('Nama Siswa & Nilai Tuntas Sebelumnya')
                            ->content(function (Grade $record) {
                                $nilaiTuntas = $record->nilai_akhir;
                                return "Nama Siswa: {$record->student?->name} (Kelas: {$record->classroom?->name}) | Nilai Tuntas: {$nilaiTuntas} (Predikat: {$record->predikat})";
                            }),

                        Forms\Components\Select::make('tp_pengayaan_id')
                            ->label('1. TP yang Dikembangkan (Wajib, sebagai dasar tautan ke rapor)')
                            ->options(function (Grade $record) {
                                $tpIds = $record->tp_ids ?? [];
                                $query = TujuanPembelajaran::query();
                                if (!empty($tpIds)) {
                                    $query->whereIn('id', $tpIds);
                                } elseif ($record->subject_id) {
                                    $query->where('subject_id', $record->subject_id);
                                }
                                return $query->get()->mapWithKeys(function ($tp) {
                                    $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                    return [$tp->id => "({$tp->kode_tp}) {$prefix}" . Str::limit($tp->deskripsi_tp, 65)];
                                });
                            })
                            ->searchable()
                            ->required()
                            ->helperText('Pilih Tujuan Pembelajaran yang dikembangkan sebagai dasar tautan ke rapor.'),

                        Forms\Components\Textarea::make('upgraded_tp_description')
                            ->label('2. Upgrade TP Pengayaan (Manual / Rumusan Baru untuk Rapor)')
                            ->placeholder("Contoh (Upgrade TP):\nMampu merancang prosedur kerja mandiri dan menganalisis studi kasus nyata penerapan aljabar linier dalam pemodelan data sederhana.")
                            ->rows(3)
                            ->required()
                            ->helperText('Rumusan kompetensi tingkat lanjut hasil upgrade TP untuk dicantumkan pada rapor / portofolio.')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('bentuk_pengayaan')
                            ->label('3. Bentuk Pengayaan')
                            ->options([
                                'soal_tingkat_lanjut' => 'Soal Tingkat Lanjut (HOTS)',
                                'projek_mini'        => 'Projek Mini',
                                'tutor_sebaya'        => 'Tutor Sebaya',
                                'studi_kasus'         => 'Studi Kasus',
                                'praktik_lanjutan'    => 'Praktik Lanjutan',
                                'kustom'              => 'Bentuk Lainnya (Input Sendiri)',
                            ])
                            ->default('projek_mini')
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('custom_bentuk_pengayaan')
                            ->label('Nama Bentuk Pengayaan (Input Sendiri)')
                            ->placeholder('Contoh: Karya Tulis Eksplorasi Mandiri')
                            ->visible(fn (Forms\Get $get) => $get('bentuk_pengayaan') === 'kustom')
                            ->required(fn (Forms\Get $get) => $get('bentuk_pengayaan') === 'kustom'),

                        Forms\Components\TextInput::make('link_drive_pengayaan')
                            ->label('4. Link Google Drive Berkas Pengayaan (Upload Drive)')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tersedia link drive untuk tautan berkas soal pengayaan, panduan penugasan, atau hasil portofolio siswa.')
                            ->maxLength(2048)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('tanggal_pengayaan')
                            ->label('5. Tanggal Pelaksanaan Pengayaan')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('nilai_pengayaan')
                            ->label('6. Nilai Pengayaan (0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('Contoh: 95')
                            ->prefix('Skor:')
                            ->required(),

                        Forms\Components\Textarea::make('catatan_pengayaan')
                            ->label('7. Catatan Guru Pengayaan (Wajib Isi)')
                            ->placeholder('Contoh: Mampu merancang prosedur kerja sendiri dengan tingkat inisiatif dan kemandirian yang sangat baik.')
                            ->rows(3)
                            ->required()
                            ->helperText('Wajib diisi, misalnya “Mampu merancang prosedur kerja sendiri”.')
                            ->columnSpanFull(),
                    ])
                    ->fillForm(fn (Grade $record) => [
                        'tp_pengayaan_id'         => $record->tp_pengayaan_id ?? (is_array($record->tp_ids) ? reset($record->tp_ids) : null),
                        'upgraded_tp_description' => $record->upgraded_tp_description,
                        'bentuk_pengayaan'        => $record->bentuk_pengayaan ?? 'projek_mini',
                        'custom_bentuk_pengayaan' => $record->custom_bentuk_pengayaan,
                        'link_drive_pengayaan'    => $record->link_drive_pengayaan,
                        'tanggal_pengayaan'       => $record->tanggal_pengayaan ?? now(),
                        'nilai_pengayaan'         => $record->nilai_pengayaan ?? $record->nilai_akhir,
                        'catatan_pengayaan'       => $record->catatan_pengayaan ?? 'Mampu merancang prosedur kerja sendiri',
                    ])
                    ->action(function (Grade $record, array $data) {
                        $record->update([
                            'is_pengayaan'            => true,
                            'tp_pengayaan_id'         => $data['tp_pengayaan_id'],
                            'upgraded_tp_description' => $data['upgraded_tp_description'],
                            'bentuk_pengayaan'        => $data['bentuk_pengayaan'],
                            'custom_bentuk_pengayaan' => $data['custom_bentuk_pengayaan'] ?? null,
                            'link_drive_pengayaan'    => $data['link_drive_pengayaan'] ?? null,
                            'tanggal_pengayaan'       => $data['tanggal_pengayaan'],
                            'nilai_pengayaan'         => $data['nilai_pengayaan'],
                            'catatan_pengayaan'       => $data['catatan_pengayaan'],
                        ]);

                        Notification::make()
                            ->title('Program Pengayaan Disimpan')
                            ->body("Upgrade TP dan pengayaan untuk {$record->student?->name} berhasil dicatat.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageGrades::route('/'),
        ];
    }
}
