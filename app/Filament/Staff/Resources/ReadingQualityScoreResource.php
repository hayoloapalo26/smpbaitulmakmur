<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ReadingQualityScoreResource\Pages;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\ReadingQualityScore;
use App\Models\Student;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReadingQualityScoreResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(array_merge(\App\Models\User::QURAN_TEACHER_ROLES, ['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(array_merge(\App\Models\User::QURAN_TEACHER_ROLES, ['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']));
    }

    protected static ?string $model = ReadingQualityScore::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Skor Kualitas Bacaan & Tahsin';
    protected static ?string $pluralModelLabel = 'Skor Kualitas Bacaan & Tahsin';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        $user = auth()->user();
        if ($user?->hasRole('koordinator_madin')) {
            return 'Madrasah Diniyah (Madin)';
        }
        if ($user?->isQuranTeacher()) {
            return 'Fitur Guru Al-Qur\'an';
        }
        return 'Kesiswaan & Karakter';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        if ($user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return $query;
        }
        return $query->where('teacher_id', $user->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')
                    ->default(fn () => Auth::id()),

                Forms\Components\Select::make('program')
                    ->label('1. Kelompok / Kelas Al-Qur\'an')
                    ->options([
                        'tahsin'           => '📖 Kelas Tahsin (Kualitas & Kaidah Tajwid)',
                        'yanbua'           => '📖 Kelas Yanbu\'a (Metode Baca Tulis Al-Qur\'an)',
                        'tahfidz_dasar'    => '📖 Kelas Tahfidz Dasar (Juz 30 / Juz \'Amma)',
                        'tahfidz_lanjutan' => '📖 Kelas Tahfidz Lanjutan (Juz 1-29)',
                        'tahfidz'          => '📖 Kelas Tahfidz Al-Qur\'an (Umum)',
                    ])
                    ->default(function () {
                        $user = Auth::user();
                        if ($user?->hasRole(User::ROLE_GURU_QURAN_TAHSIN)) return 'tahsin';
                        if ($user?->hasRole(User::ROLE_GURU_QURAN_YANBUA)) return 'yanbua';
                        if ($user?->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_DASAR)) return 'tahfidz_dasar';
                        if ($user?->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_LANJUTAN)) return 'tahfidz_lanjutan';
                        return 'tahsin';
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('student_id', null))
                    ->helperText('Pilih kelas Al-Qur\'an untuk memfilter siswa sesuai penempatan Waka Kurikulum.'),

                Forms\Components\Select::make('classroom_id')
                    ->label('2. Filter Rombel Reguler (Opsional)')
                    ->options(function () {
                        $user = Auth::user();
                        if (!$user) return [];
                        if ($user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'superadmin', 'kepala_sekolah'])) {
                            return Classroom::orderBy('name')->pluck('name', 'id');
                        }
                        if ($user->isQuranTeacher()) {
                            $assignedClassroomIds = Student::where('guru_quran_id', $user->id)
                                ->whereNotNull('classroom_id')
                                ->pluck('classroom_id')
                                ->toArray();
                            $teacherClassroomIds = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('classroom_id')->toArray();
                            $allIds = array_unique(array_merge($assignedClassroomIds, $teacherClassroomIds));
                            if (!empty($allIds)) {
                                return Classroom::whereIn('id', $allIds)->orderBy('name')->pluck('name', 'id');
                            }
                        }
                        $classroomIds = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('classroom_id')->toArray();
                        return Classroom::whereIn('id', $classroomIds)->orderBy('name')->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('student_id', null))
                    ->dehydrated(false)
                    ->helperText('Pilih rombel jika ingin menyaring siswa per rombel reguler (7A, 7B, dll).'),

                Forms\Components\Select::make('student_id')
                    ->label('3. Nama Siswa (Lengkap Dengan Status Kelas Al-Qur\'an)')
                    ->options(function (Forms\Get $get) {
                        $user = Auth::user();
                        if (!$user) return [];
                        $classroomId = $get('classroom_id');
                        $selectedProgram = $get('program');

                        $query = Student::query()->with(['classroom', 'guruQuran']);

                        // 1. Isolasi Lembaga jika bukan superadmin
                        if ($user->institution_id && $user->institution_id != 1 && !$user->hasRole('superadmin')) {
                            if (\Illuminate\Support\Facades\Schema::hasColumn('students', 'institution_id')) {
                                $query->where('students.institution_id', $user->institution_id);
                            } else {
                                $query->whereHas('classroom', fn ($cq) => $cq->where('institution_id', $user->institution_id));
                            }
                        }

                        // 2. Filter berdasarkan program / kelas Al-Qur'an yang dipilih
                        if ($selectedProgram && in_array($selectedProgram, ['tahsin', 'yanbua', 'tahfidz_dasar', 'tahfidz_lanjutan'])) {
                            $query->where('kelas_quran', $selectedProgram);

                            // Jika guru Al-Qur'an login, prioritaskan siswa binaan guru tersebut
                            if ($user->isQuranTeacher()) {
                                $hasAssignedToMe = (clone $query)->where('guru_quran_id', $user->id)->exists();
                                if ($hasAssignedToMe) {
                                    $query->where('guru_quran_id', $user->id);
                                }
                            }
                        } elseif ($user->isQuranTeacher()) {
                            $specialization = match (true) {
                                $user->hasRole(User::ROLE_GURU_QURAN_YANBUA) => 'yanbua',
                                $user->hasRole(User::ROLE_GURU_QURAN_TAHSIN) => 'tahsin',
                                $user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_DASAR) => 'tahfidz_dasar',
                                $user->hasRole(User::ROLE_GURU_QURAN_TAHFIDZ_LANJUTAN) => 'tahfidz_lanjutan',
                                default => null,
                            };
                            $query->where(function ($q) use ($user, $specialization) {
                                $q->where('guru_quran_id', $user->id);
                                if ($specialization) {
                                    $q->orWhere('kelas_quran', $specialization);
                                }
                            });
                        }

                        // 3. Filter rombel reguler jika dipilih
                        if ($classroomId) {
                            $query->where('classroom_id', $classroomId);
                        }

                        return $query->orderBy('name')->get()->mapWithKeys(function ($s) {
                            $quranLabel = $s->kelas_quran_label 
                                ? " • [Kelas Al-Qur'an: {$s->kelas_quran_label}]" 
                                : " • [⚠️ Belum Ada Kelas Al-Qur'an]";
                            $rombel = $s->classroom?->name ? " [Rombel: {$s->classroom->name}]" : '';
                            $guru = $s->guruQuran?->name ? " (Guru: {$s->guruQuran->name})" : '';
                            $nis = $s->nis ? " (NIS: {$s->nis})" : '';
                            return [
                                $s->id => "{$s->name}{$nis}{$quranLabel}{$guru}{$rombel}"
                            ];
                        });
                    })
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $student = Student::with(['classroom', 'guruQuran'])->find($state);
                            if ($student?->kelas_quran) {
                                $set('program', $student->kelas_quran);
                            }
                            if ($student?->classroom_id) {
                                $set('classroom_id', $student->classroom_id);
                            }
                        }
                    }),

                Forms\Components\TextInput::make('jilid_halaman')
                    ->label('4. Jilid / Halaman / Juz Penilaian (Opsional)')
                    ->placeholder('Contoh: Jilid 3 Hal. 15 / Juz 30 Surah An-Naba')
                    ->helperText('Catatan materi yang dinilai (misal Jilid Yanbu\'a atau surat yang diuji).'),

                Forms\Components\TextInput::make('scale_score')
                    ->label('5. Skor Kualitas Bacaan (0 - 100)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required()
                    ->prefix('Skor:')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state !== null && $state !== '') {
                            $scale = Grade::determineScale((float)$state);
                            $set('predikat', $scale['predikat']);
                        }
                    })
                    ->helperText(function (Forms\Get $get) {
                        $score = $get('scale_score');
                        if ($score === null || $score === '') {
                            return '💡 Skala Nilai: 100 (Excellent) | 90 - 99 (Sangat Baik) | 80 - 89 (Baik) | 70 - 79 (Cukup Baik) | < 70 (Perlu Pembinaan).';
                        }
                        $scale = Grade::determineScale((float)$score);
                        if ($scale['is_tuntas']) {
                            return "✅ Predikat: {$scale['label']} (Tuntas).";
                        }
                        return "⚠️ Predikat: {$scale['label']} (Perlu Pembinaan Intensif).";
                    }),

                Forms\Components\Hidden::make('predikat'),

                Forms\Components\DatePicker::make('tanggal')
                    ->label('6. Tanggal Penilaian')
                    ->default(Carbon::now())
                    ->required(),

                Forms\Components\Textarea::make('catatan_guru')
                    ->label('7. Catatan Evaluasi Guru (Makhraj, Tajwid, Fashahah, Adab)')
                    ->placeholder('Contoh: Makharijul huruf sangat tepat, kaidah mad thobi\'i konsisten, fashahah dan kelancaran sangat baik.')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tanggal'),

                Tables\Columns\TextColumn::make('student.name')
                    ->searchable()
                    ->label('Nama Siswa')
                    ->weight('bold')
                    ->description(fn (ReadingQualityScore $record) => 'NIS: ' . ($record->student?->nis ?? '-')),

                Tables\Columns\TextColumn::make('student.classroom.name')
                    ->label('Kelas')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Guru Penginput')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->description(fn (ReadingQualityScore $record) => $record->teacher?->getQuranSpecializationLabel() ? "[{$record->teacher->getQuranSpecializationLabel()}]" : null),

                Tables\Columns\TextColumn::make('program')
                    ->label('Program')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'yanbua'           => 'emerald',
                        'tahsin'           => 'info',
                        'tahfidz_dasar'    => 'primary',
                        'tahfidz_lanjutan' => 'purple',
                        default            => 'gray',
                    })
                    ->formatStateUsing(fn ($state, ReadingQualityScore $record) => $record->program_label),

                Tables\Columns\TextColumn::make('scale_score')
                    ->label('Skor')
                    ->sortable()
                    ->weight('bold')
                    ->color(function (ReadingQualityScore $record): string {
                        return $record->predikat_color;
                    }),

                Tables\Columns\TextColumn::make('predikat')
                    ->label('Predikat Skala')
                    ->badge()
                    ->color(fn (ReadingQualityScore $record): string => $record->predikat_color)
                    ->formatStateUsing(fn (ReadingQualityScore $record): string => $record->predikat),

                Tables\Columns\TextColumn::make('jilid_halaman')
                    ->label('Jilid / Hal.')
                    ->placeholder('-'),

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
                Tables\Filters\SelectFilter::make('program')
                    ->label('Filter Program')
                    ->options([
                        'yanbua'           => 'Yanbu\'a',
                        'tahsin'           => 'Tahsin',
                        'tahfidz_dasar'    => 'Tahfidz Dasar',
                        'tahfidz_lanjutan' => 'Tahfidz Lanjutan',
                        'tahfidz'          => 'Tahfidz (Umum)',
                    ]),

                Tables\Filters\SelectFilter::make('status_verifikasi')
                    ->label('Status Verifikasi')
                    ->options([
                        'Menunggu Verifikasi' => 'Menunggu Verifikasi',
                        'Diterima' => 'Diterima',
                        'Perlu Perbaikan' => 'Perlu Perbaikan',
                    ]),

                Tables\Filters\SelectFilter::make('teacher_id')
                    ->label('Guru Penginput')
                    ->relationship('teacher', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('terima')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status_verifikasi !== 'Diterima' && (auth()->user()?->hasAnyRole(['koordinator_madin', 'kepala_sekolah', 'waka_kurikulum', 'superadmin']) ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Terima Skor Kualitas Bacaan')
                    ->modalDescription('Apakah Anda yakin menerima dan memverifikasi skor kualitas bacaan siswa ini?')
                    ->action(function ($record) {
                        $record->update([
                            'status_verifikasi' => 'Diterima',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Skor Kualitas Berhasil Diterima & Diverifikasi')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('terima_massal')
                        ->label('Terima Semua Terpilih')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['koordinator_madin', 'kepala_sekolah', 'waka_kurikulum', 'superadmin']) ?? false)
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each->update([
                                'status_verifikasi' => 'Diterima',
                                'verified_by' => auth()->id(),
                                'verified_at' => now(),
                            ]);
                            Notification::make()
                                ->title('Seluruh Skor Terpilih Berhasil Diverifikasi')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageReadingQualityScores::route('/'),
        ];
    }
}
