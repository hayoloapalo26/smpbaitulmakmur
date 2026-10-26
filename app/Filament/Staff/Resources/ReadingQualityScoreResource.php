<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ReadingQualityScoreResource\Pages;
use App\Models\ReadingQualityScore;
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

class ReadingQualityScoreResource extends Resource
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['guru_alquran', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['guru_alquran', 'waka_kurikulum', 'superadmin']);
    }

    protected static ?string $model = ReadingQualityScore::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $modelLabel = 'Skor Kualitas Bacaan';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        if ($user->hasRole('waka_kurikulum')) {
            return $query;
        }
        return $query->where('teacher_id', $user->id);
    }

    

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $studentIds = [];

        if (!$user->hasRole('waka_kurikulum')) {
            $studentIds = TeacherSubjectClass::where('teacher_id', $user->id)
                ->join('students', 'students.classroom_id', '=', 'teacher_subject_classes.classroom_id')
                ->pluck('students.id')
                ->toArray();
        }

        return $form
            ->schema([
                Forms\Components\Hidden::make('teacher_id')
                    ->default($user->id),
                Forms\Components\Select::make('student_id')
                    ->label('Siswa')
                    ->options(function () use ($user, $studentIds) {
                        if ($user->hasRole('waka_kurikulum')) return Student::pluck('name', 'id');
                        return Student::whereIn('id', $studentIds)->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('program')
                    ->options([
                        'tahfidz' => 'Tahfidz',
                        'tahsin' => 'Tahsin',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('scale_score')
                    ->label('Skor (1-100)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->required(),
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
                Tables\Columns\TextColumn::make('program')->label('Program'),
                Tables\Columns\TextColumn::make('scale_score')->label('Skor'),
                Tables\Columns\TextColumn::make('tanggal')->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageReadingQualityScores::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
