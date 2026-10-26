<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\TeacherSupervisionResource\Pages;
use App\Models\TeacherSupervision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherSupervisionResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $model = TeacherSupervision::class;
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $modelLabel = 'Supervisi Guru';

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('teacher_id')->relationship('teacher', 'name')->required(),
                Forms\Components\Select::make('supervisor_id')->relationship('supervisor', 'name')->required(),
                Forms\Components\DatePicker::make('tanggal')->required(),
                Forms\Components\Textarea::make('hasil')->required(),
                Forms\Components\Textarea::make('catatan')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')->date(),
                Tables\Columns\TextColumn::make('teacher.name')->searchable()->label('Guru'),
                Tables\Columns\TextColumn::make('supervisor.name')->label('Supervisor'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageTeacherSupervisions::route('/')];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
