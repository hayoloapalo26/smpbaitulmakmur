<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\CharacterScoreLogResource\Pages;
use App\Models\CharacterScoreLog;
use App\Models\Student;
use App\Models\TeacherSubjectClass;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CharacterScoreLogResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Roles that already have 'Rekapitulasi & Input Karakter' (CharacterScoreReport) in their sidebar do not need a duplicate menu
        if ($user->hasAnyRole(['wali_kelas', 'guru_kelas', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return false;
        }

        return $user->hasAnyRole(['guru_mapel']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['wali_kelas', 'guru_kelas', 'guru_mapel', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']);
    }

    protected static ?string $model = CharacterScoreLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Penilaian Karakter Siswa';
    protected static ?string $modelLabel = 'Skor Karakter';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user->hasRole(['waka_kesiswaan', 'bk', 'kepala_sekolah'])) {
            return $query;
        }

        // Only show records submitted by this teacher
        // Or if we strictly follow scoping: see records of students they teach.
        // Let's scope to students they teach.
        $studentIds = TeacherSubjectClass::where('teacher_id', $user->id)
            ->join('students', 'students.classroom_id', '=', 'teacher_subject_classes.classroom_id')
            ->pluck('students.id')
            ->toArray();

        return $query->whereIn('student_id', $studentIds);
    }

    

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $studentIds = [];

        if (!$user->hasRole(['waka_kesiswaan', 'bk', 'kepala_sekolah'])) {
            $studentIds = TeacherSubjectClass::where('teacher_id', $user->id)
                ->join('students', 'students.classroom_id', '=', 'teacher_subject_classes.classroom_id')
                ->pluck('students.id')
                ->toArray();
        }

        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->label('Siswa')
                    ->options(function () use ($user, $studentIds) {
                        if ($user->hasRole(['waka_kesiswaan', 'bk', 'kepala_sekolah'])) {
                            return Student::pluck('name', 'id');
                        }
                        return Student::whereIn('id', $studentIds)->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(),
                Forms\Components\Hidden::make('source')
                    ->default('guru_kelas'),
                Forms\Components\Hidden::make('submitted_by')
                    ->default(Auth::id()),
                Forms\Components\Select::make('category')
                    ->label('Aspek Penilaian')
                    ->options([
                        'kepribadian' => 'Kepribadian Siswa',
                        'sosial' => 'Aspek Sosial',
                        'presensi' => 'Presensi',
                        'leadership' => 'Leadership',
                        'tanggungjawab' => 'Tanggungjawab',
                    ])
                    ->live()
                    ->required(),
                Forms\Components\Select::make('description')
                    ->label('Kondisi / Perilaku')
                    ->options(fn (Forms\Get $get): array => match ($get('category')) {
                        'kepribadian' => ['Ramai' => 'Ramai', 'Tidak Ramai' => 'Tidak Ramai'],
                        'sosial' => ['Ganggu Teman' => 'Ganggu Teman', 'Tidak Ganggu Teman' => 'Tidak Ganggu Teman'],
                        'presensi' => ['Alpa' => 'Alpa', 'Terlambat' => 'Terlambat', 'Hadir' => 'Hadir'],
                        'leadership' => ['Tidak Berani Maju' => 'Tidak Berani Maju', 'Berani Maju' => 'Berani Maju'],
                        'tanggungjawab' => ['Tidak Mengerjakan PR/Tugas' => 'Tidak Mengerjakan PR/Tugas', 'Mengerjakan PR/Tugas' => 'Mengerjakan PR/Tugas'],
                        default => [],
                    })
                    ->required(),
                Forms\Components\Hidden::make('point_change')
                    ->default(0), // Updated via mutateFormDataUsing
                Forms\Components\DatePicker::make('tanggal')
                    ->default(Carbon::now())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')->searchable()->label('Siswa')->searchable(),
                Tables\Columns\TextColumn::make('source')->label('Sumber'),
                Tables\Columns\TextColumn::make('category')->label('Kategori'),
                Tables\Columns\TextColumn::make('point_change')
                    ->label('Poin')
                    ->color(fn (int $state): string => match (true) {
                        $state > 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('tanggal')->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCharacterScoreLogs::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
