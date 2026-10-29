<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\GradeResource\Pages;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
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
    protected static ?string $navigationLabel = 'Daftar Nilai Siswa';
    protected static ?string $modelLabel = 'Nilai Siswa';
    protected static ?string $pluralModelLabel = 'Daftar Nilai Siswa';
    protected static ?int $navigationSort = 5;

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

        // Guru Mapel: Nilai pada mata pelajaran yang diampu
        $subjectIds = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('subject_id')->toArray();

        // Wali Kelas: Nilai siswa-siswi di kelas binaannya
        $waliClassroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
        $waliStudentIds = Student::whereIn('classroom_id', $waliClassroomIds)->pluck('id')->toArray();

        return $query->where(function ($q) use ($user, $subjectIds, $waliStudentIds) {
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
                $waliClassroomIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
                $classroomIds = array_unique(array_merge($classroomIds, $waliClassroomIds));
            }

            $studentIds = Student::whereIn('classroom_id', $classroomIds)->pluck('id')->toArray();
        }

        return $form
            ->schema([
                Forms\Components\Section::make('📚 Identitas Kelas & Siswa')
                    ->description('Pilih kelas terlebih dahulu untuk memunculkan daftar siswa yang berada di kelas tersebut.')
                    ->schema([
                        Forms\Components\Hidden::make('teacher_id')
                            ->default(fn () => Auth::id()),

                        Forms\Components\Select::make('classroom_id')
                            ->label('1. Kelas')
                            ->options(function () use ($isPrivileged, $user, $isWaliKelas) {
                                if ($isPrivileged) {
                                    return Classroom::orderBy('name')->pluck('name', 'id');
                                }
                                $tscClassrooms = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('classroom_id')->toArray();
                                if ($isWaliKelas) {
                                    $waliClassrooms = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
                                    $tscClassrooms = array_unique(array_merge($tscClassrooms, $waliClassrooms));
                                }
                                return Classroom::whereIn('id', $tscClassrooms)->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('student_id', null)),

                        Forms\Components\Select::make('student_id')
                            ->label('2. Nama Siswa')
                            ->options(function (Forms\Get $get) use ($isPrivileged, $studentIds) {
                                $classroomId = $get('classroom_id');
                                if ($classroomId) {
                                    return Student::where('classroom_id', $classroomId)->orderBy('name')->pluck('name', 'id');
                                }
                                if ($isPrivileged) {
                                    return Student::orderBy('name')->pluck('name', 'id');
                                }
                                return Student::whereIn('id', $studentIds)->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->helperText('Daftar siswa otomatis disesuaikan dengan kelas yang Anda pilih di atas.'),

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
                            ->helperText('Tanggal otomatis disesuaikan dengan hari saat guru menginput.'),
                    ])->columns(3),

                Forms\Components\Section::make('📝 Parameter Penilaian & Tujuan Pembelajaran (TP)')
                    ->description('Tentukan tipe nilai, bentuk penilaian, lingkup materi, serta pilihan Tujuan Pembelajaran (TP).')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipe Nilai (5 Pilihan)')
                            ->options([
                                'formatif'     => '1. Nilai Formatif / Harian',
                                'keterampilan' => '2. Keterampilan / Praktik',
                                'sts'          => '3. STS (Sumatif Tengah Semester)',
                                'sas'          => '4. SAS (Sumatif Akhir Semester)',
                                'kustom'       => '5. Tipe Nilai Kustom (Input Sendiri)',
                            ])
                            ->default('formatif')
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('custom_type_name')
                            ->label('Nama Tipe Nilai Kustom')
                            ->placeholder('Contoh: Nilai Proyek Kolaboratif / Portofolio')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'kustom')
                            ->required(fn (Forms\Get $get) => $get('type') === 'kustom')
                            ->helperText('Isi nama tipe penilaian sesuai kebutuhan Anda.'),

                        Forms\Components\Select::make('bentuk_penilaian')
                            ->label('Bentuk Penilaian')
                            ->options([
                                'quiz'           => 'Quiz / Kuis Singkat',
                                'diskusi'        => 'Diskusi & Keaktifan',
                                'ulangan_harian' => 'Ulangan Harian',
                                'presentasi'     => 'Presentasi / Unjuk Kerja',
                                'kir'            => 'KIR (Karya Ilmiah Remaja)',
                                'kustom'         => 'Bentuk Penilaian Sendiri (Kustom)',
                            ])
                            ->default('ulangan_harian')
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('custom_bentuk_penilaian')
                            ->label('Nama Bentuk Penilaian Kustom')
                            ->placeholder('Contoh: Praktikum Lapangan Mandiri')
                            ->visible(fn (Forms\Get $get) => $get('bentuk_penilaian') === 'kustom')
                            ->required(fn (Forms\Get $get) => $get('bentuk_penilaian') === 'kustom'),

                        Forms\Components\TextInput::make('materi')
                            ->label('Materi Pokok yang Dinilai')
                            ->placeholder('Contoh: Operasi Aljabar dan Persamaan Linier Satu Variabel')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('tp_ids')
                            ->label('Tujuan Pembelajaran (TP) Terkait [Multiple Choice]')
                            ->multiple()
                            ->options(function (Forms\Get $get) {
                                $subjectId = $get('subject_id');
                                $classroomId = $get('classroom_id');
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
                                return $query->get()->mapWithKeys(function ($tp) {
                                    $prefix = $tp->materi ? "[{$tp->materi}] " : '';
                                    return [$tp->id => "{$prefix}" . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 65)];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Pilih satu atau lebih TP yang diuji. Bersumber langsung dari Perangkat Ajar (TP).')
                            ->columnSpanFull()
                            ->required(),

                        Forms\Components\TextInput::make('score')
                            ->label('Input Nilai Siswa (0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->prefix('Skor:')
                            ->helperText('💡 Catatan: Siswa yang memperoleh nilai minimal 95 berhak mendapatkan program Pengayaan & Upgrade TP Level 2.')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('catatan_guru')
                            ->label('Catatan Guru per TP / Umpan Balik Siswa (Wajib)')
                            ->placeholder('Contoh: Ananda Ahmad sangat baik dalam memahami konsep aljabar, namun perlu berlatih lebih teliti pada operasi perkalian pecahan dan penyelesaian soal cerita.')
                            ->rows(3)
                            ->required()
                            ->helperText('Wajib diisi sebagai rekam evaluasi dan umpan balik perkembangan capaian belajar murid.')
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
                    ->formatStateUsing(fn ($state, Grade $record) => $state === 'kustom' ? ($record->custom_bentuk_penilaian ?: 'Kustom') : ucfirst(str_replace('_', ' ', (string)$state)))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai')
                    ->weight('bold')
                    ->sortable()
                    ->color(function (float $state): string {
                        if ($state >= 95) return 'warning'; // Bintang Pengayaan
                        if ($state >= 75) return 'success';
                        return 'danger';
                    })
                    ->description(function (Grade $record) {
                        if ($record->score >= 95) {
                            return $record->is_pengayaan ? '⭐ Pengayaan Selesai' : '⭐ Memenuhi Pengayaan';
                        }
                        if ($record->is_remedial) {
                            return '🔄 Remedial: ' . $record->nilai_remedial;
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('tp_count')
                    ->label('TP Diuji')
                    ->state(fn (Grade $record) => count($record->tp_ids ?? []) . ' TP')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tgl Input')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('catatan_guru')
                    ->label('Catatan Guru')
                    ->limit(40)
                    ->wrap()
                    ->tooltip(fn (Grade $record) => $record->catatan_guru),

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

                Tables\Filters\SelectFilter::make('semester')
                    ->options([
                        'Ganjil' => 'Ganjil',
                        'Genap'  => 'Genap',
                    ]),
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

                // 🔄 AKSI REMEDIAL: Untuk TP yang belum tuntas / perlu perbaikan nilai
                Tables\Actions\Action::make('remedial')
                    ->label('Remedial')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalHeading(fn (Grade $record) => "🔄 Remedial / Perbaikan Nilai Siswa: {$record->student?->name}")
                    ->modalDescription('Inputkan tanggal dan nilai ulang bagi siswa yang belum tuntas pada Tujuan Pembelajaran (TP) terkait.')
                    ->modalSubmitActionLabel('Simpan Nilai Remedial')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_remedial')
                            ->label('Tanggal Pelaksanaan Remedial')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('nilai_remedial')
                            ->label('Nilai Ulang Remedial (0 - 100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->placeholder('Contoh: 78')
                            ->prefix('Nilai:')
                            ->required(),

                        Forms\Components\Textarea::make('catatan_remedial')
                            ->label('Catatan Guru Remedial & Bentuk Perbaikan (Wajib)')
                            ->placeholder('Contoh: Siswa telah diberikan penjelasan ulang konsep dasar dan mengerjakan 5 soal latihan terbimbing dengan hasil tuntas.')
                            ->rows(3)
                            ->required()
                            ->helperText('Wajib diisi sebagai bukti evaluasi tindak lanjut penuntasan TP.')
                            ->columnSpanFull(),
                    ])
                    ->fillForm(fn (Grade $record) => [
                        'tanggal_remedial' => $record->tanggal_remedial ?? now(),
                        'nilai_remedial'   => $record->nilai_remedial,
                        'catatan_remedial' => $record->catatan_remedial,
                    ])
                    ->action(function (Grade $record, array $data) {
                        $record->update([
                            'is_remedial'      => true,
                            'tanggal_remedial' => $data['tanggal_remedial'],
                            'nilai_remedial'   => $data['nilai_remedial'],
                            'catatan_remedial' => $data['catatan_remedial'],
                        ]);

                        Notification::make()
                            ->title('Nilai Remedial Disimpan')
                            ->body("Data perbaikan nilai untuk {$record->student?->name} berhasil diperbarui.")
                            ->success()
                            ->send();
                    }),

                // ⭐ AKSI PENGAYAAN: Khusus siswa yang telah mencapai TP dengan nilai tinggi MINIMAL 95
                Tables\Actions\Action::make('pengayaan')
                    ->label('Pengayaan (⭐ Min. 95)')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->visible(fn (Grade $record) => $record->score >= 95)
                    ->modalHeading(fn (Grade $record) => "⭐ Program Pengayaan & Upgrade TP Level 2: {$record->student?->name}")
                    ->modalDescription('Siswa telah mencapai nilai tinggi (minimal 95). Berikan penugasan pengayaan dan upgrade TP khusus ke level yang lebih berbobot (Level 2).')
                    ->modalSubmitActionLabel('Simpan Program Pengayaan')
                    ->form([
                        Forms\Components\Select::make('tp_pengayaan_id')
                            ->label('1. TP yang Dikembangkan (Dari TP yang Diinputkan)')
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
                                    return [$tp->id => "{$prefix}" . \Illuminate\Support\Str::limit($tp->deskripsi_tp, 65)];
                                });
                            })
                            ->searchable()
                            ->required()
                            ->helperText('Pilih Tujuan Pembelajaran asal yang dicapai dengan skor tinggi.'),

                        Forms\Components\Textarea::make('upgraded_tp_description')
                            ->label('2. Upgrade TP Khusus Siswa Ini (TP Level 2 Berbobot Lebih Tinggi)')
                            ->placeholder("Contoh (TP Level 2):\nMenganalisis studi kasus nyata penerapan aljabar linier dalam pemodelan keuangan sederhana dan mempresentasikan rekomendasinya secara mandiri di hadapan kelas.")
                            ->rows(3)
                            ->required()
                            ->helperText('Rumusan TP Level 2 khusus untuk siswa ini agar mendapatkan capaian kompetensi yang lebih mendalam dan aplikatif.')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('bentuk_pengayaan')
                            ->label('3. Bentuk Pengayaan')
                            ->options([
                                'soal_tingkat_lanjut' => 'Soal Tingkat Lanjut (HOTS)',
                                'project_mini'        => 'Project Mini',
                                'tutor_sebaya'        => 'Tutor Sebaya',
                                'studi_kasus'         => 'Studi Kasus',
                                'praktek_lanjutan'    => 'Praktek Lanjutan',
                                'kustom'              => 'Bentuk Pengayaan Lainnya (Kustom)',
                            ])
                            ->default('project_mini')
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('custom_bentuk_pengayaan')
                            ->label('Nama Bentuk Pengayaan Kustom')
                            ->placeholder('Contoh: Karya Tulis Eksplorasi Mandiri')
                            ->visible(fn (Forms\Get $get) => $get('bentuk_pengayaan') === 'kustom')
                            ->required(fn (Forms\Get $get) => $get('bentuk_pengayaan') === 'kustom'),

                        Forms\Components\TextInput::make('link_drive_pengayaan')
                            ->label('4. Tautan Link Google Drive Berkas Bentuk Pengayaan (Upload Drive)')
                            ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                            ->url()
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tautkan link Google Drive dokumen soal, panduan tugas, atau portofolio pengayaan siswa untuk menjaga kapasitas hosting.')
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
                            ->placeholder('Contoh: 98')
                            ->prefix('Skor:')
                            ->required(),

                        Forms\Components\Textarea::make('catatan_pengayaan')
                            ->label('7. Catatan Guru Pengayaan (Wajib)')
                            ->placeholder('Contoh: Ananda sangat antusias menyelesaikan project mini analisis data cuaca dan mampu menjelaskan hasilnya kepada rekan sebaya dengan sangat fasih.')
                            ->rows(3)
                            ->required()
                            ->helperText('Wajib diisi sebagai catatan apresiasi dan pembinaan kompetensi tingkat lanjut.')
                            ->columnSpanFull(),
                    ])
                    ->fillForm(fn (Grade $record) => [
                        'tp_pengayaan_id'         => $record->tp_pengayaan_id ?? (is_array($record->tp_ids) ? reset($record->tp_ids) : null),
                        'upgraded_tp_description' => $record->upgraded_tp_description,
                        'bentuk_pengayaan'        => $record->bentuk_pengayaan ?? 'project_mini',
                        'custom_bentuk_pengayaan' => $record->custom_bentuk_pengayaan,
                        'link_drive_pengayaan'    => $record->link_drive_pengayaan,
                        'tanggal_pengayaan'       => $record->tanggal_pengayaan ?? now(),
                        'nilai_pengayaan'         => $record->nilai_pengayaan ?? $record->score,
                        'catatan_pengayaan'       => $record->catatan_pengayaan,
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
                            ->body("Upgrade TP Level 2 dan pengayaan untuk {$record->student?->name} berhasil dicatat.")
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
