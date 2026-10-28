<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectResource extends Resource
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    protected static ?string $model = Subject::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationLabel = 'Mata Pelajaran SMP';
    protected static ?string $modelLabel = 'Mata Pelajaran';
    protected static ?string $pluralModelLabel = 'Mata Pelajaran';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Mata Pelajaran')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Mata Pelajaran')
                            ->placeholder('Contoh: Matematika, Fiqih/Tarikh, dsb.')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('kode')
                            ->label('Kode Mata Pelajaran')
                            ->placeholder('Contoh: MTK, FQH, dsb.')
                            ->required()
                            ->maxLength(50),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Mata Pelajaran')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManageSubjects::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
