<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\HumasProgramResource\Pages;
use App\Models\HumasProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HumasProgramResource extends Resource
{
    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $model = HumasProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $modelLabel = 'Program Humas';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')->required(),
                Forms\Components\Select::make('status')
                    ->options(['sudah' => 'Sudah', 'belum' => 'Belum'])
                    ->required(),
                Forms\Components\DatePicker::make('tanggal')->required(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\ViewEntry::make('summary_table')
                    ->view('filament.staff.components.program-kerja-summary-table')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')->searchable(),
                Tables\Columns\TextColumn::make('tanggal')->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sudah' => 'success',
                        'belum' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => 'Ringkasan Program Kerja: ' . ($record->nama ?? 'Program Kerja'))
                    ->modalWidth('4xl'),
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
        return ['index' => Pages\ManageHumasPrograms::route('/')];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
