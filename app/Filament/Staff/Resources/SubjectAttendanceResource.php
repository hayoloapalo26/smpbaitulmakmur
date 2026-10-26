<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\SubjectAttendanceResource\Pages;
use App\Models\SubjectAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectAttendanceResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $model = SubjectAttendance::class;
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $modelLabel = 'Presensi Mapel';

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('teaching_journal_id')->relationship('teachingJournal', 'materi')->required(),
                Forms\Components\Select::make('student_id')->relationship('student', 'name')->required(),
                Forms\Components\Select::make('status')->options(['hadir'=>'Hadir','izin'=>'Izin','sakit'=>'Sakit','alpa'=>'Alpa'])->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teachingJournal.tanggal')->date()->label('Tanggal'),
                Tables\Columns\TextColumn::make('student.name')->searchable()->label('Siswa'),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSubjectAttendances::route('/')];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
