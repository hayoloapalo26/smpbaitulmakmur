<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\GradeResource\Pages;
use App\Models\Grade;
use App\Models\Student;
use App\Models\TeacherSubjectClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GradeResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole([
            'guru_mapel',
            'guru_alquran',
            'waka_kurikulum',
            'kepala_sekolah',
            'superadmin',
        ]) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'guru_mapel',
            'guru_alquran',
            'guru_kelas',
            'wali_kelas',
            'waka_kurikulum',
            'kepala_sekolah',
            'superadmin',
        ]) ?? false;
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

        // Guru Mapel: Grades for subjects they teach
        $subjectIds = TeacherSubjectClass::where('teacher_id', $user->id)->pluck('subject_id')->toArray();
        
        // Wali Kelas: Grades of students in their classroom
        $waliClassroomIds = \App\Models\Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
        $waliStudentIds = Student::whereIn('classroom_id', $waliClassroomIds)->pluck('id')->toArray();

        return $query->where(function ($q) use ($subjectIds, $waliStudentIds) {
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
            if (!$hasCondition) {
                $q->whereRaw('1=0');
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
                $waliClassroomIds = \App\Models\Classroom::where('wali_kelas_id', $user->id)->pluck('id')->toArray();
                $classroomIds = array_unique(array_merge($classroomIds, $waliClassroomIds));
            }

            $studentIds = Student::whereIn('classroom_id', $classroomIds)->pluck('id')->toArray();
        }

        return $form
            ->schema([
                Forms\Components\Section::make('Form Input Nilai Siswa')
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->label('Siswa')
                            ->options(function () use ($isPrivileged, $studentIds) {
                                if ($isPrivileged) return Student::orderBy('name')->pluck('name', 'id');
                                return Student::whereIn('id', $studentIds)->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('subject_id')
                            ->label('Mata Pelajaran')
                            ->options(function () use ($isPrivileged, $isWaliKelas, $subjectIds) {
                                if ($isPrivileged || $isWaliKelas) return \App\Models\Subject::orderBy('name')->pluck('name', 'id');
                                return \App\Models\Subject::whereIn('id', $subjectIds)->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('academic_year_id')
                            ->relationship('academicYear', 'name')
                            ->label('Tahun Ajaran')
                            ->required(),
                        Forms\Components\Select::make('semester')
                            ->options([
                                'Ganjil' => 'Ganjil',
                                'Genap'  => 'Genap',
                            ])
                            ->default('Ganjil')
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Tipe Nilai')
                            ->options([
                                'ujian'        => 'Ujian (UTS / UAS)',
                                'pengetahuan'  => 'Pengetahuan / Tugas Harian',
                                'keterampilan' => 'Keterampilan / Praktik',
                            ])
                            ->default('ujian')
                            ->required(),
                        Forms\Components\TextInput::make('score')
                            ->label('Nilai (0 - 100)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100),
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
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('student.classroom.name')
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
                    ->label('Kategori Nilai')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pengetahuan'  => 'primary',
                        'keterampilan' => 'warning',
                        'ujian'        => 'success',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pengetahuan'  => 'Pengetahuan (P)',
                        'keterampilan' => 'Keterampilan (K)',
                        'ujian'        => 'Ujian (UTS/UAS)',
                        default        => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai')
                    ->weight('bold')
                    ->sortable()
                    ->color(fn (float $state): string => $state >= 75 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('semester')
                    ->label('Semester')
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->relationship('subject', 'name')
                    ->label('Filter Mata Pelajaran')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Filter Kategori')
                    ->options([
                        'pengetahuan'  => 'Pengetahuan',
                        'keterampilan' => 'Keterampilan',
                        'ujian'        => 'Ujian',
                    ]),
                Tables\Filters\SelectFilter::make('semester')
                    ->options([
                        'Ganjil' => 'Ganjil',
                        'Genap'  => 'Genap',
                    ]),
                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->relationship('academicYear', 'name')
                    ->label('Tahun Ajaran'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
