<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\LetterResource\Pages;
use App\Models\Letter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LetterResource extends Resource
{
public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['tata_usaha', 'superadmin', 'kepala_sekolah']);
    }

    protected static ?string $model = Letter::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Administrasi & Data Induk';
    protected static ?int $navigationSort = 5;
    protected static ?string $modelLabel = 'Surat';

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Jenis Surat')
                    ->options(['masuk' => 'Masuk', 'keluar' => 'Keluar'])
                    ->required(),
                Forms\Components\TextInput::make('nomor')->label('Nomor Surat')->required(),
                Forms\Components\DatePicker::make('tanggal')->required(),
                Forms\Components\TextInput::make('perihal')->required(),
                Forms\Components\TextInput::make('file')
                    ->label('Link Google Drive Surat')
                    ->placeholder('https://drive.google.com/file/d/.../view?usp=sharing')
                    ->url()
                    ->prefixIcon('heroicon-o-link')
                    ->helperText('Tautkan link Google Drive dokumen surat untuk menghemat ruang penyimpanan hosting.')
                    ->maxLength(2048),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'masuk' => 'success',
                        'keluar' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('nomor')->searchable(),
                Tables\Columns\TextColumn::make('tanggal')->date(),
                Tables\Columns\TextColumn::make('perihal')->searchable(),
                Tables\Columns\TextColumn::make('file')
                    ->label('Dokumen')
                    ->icon(fn ($state) => filled($state) ? 'heroicon-m-arrow-top-right-on-square' : null)
                    ->color(fn ($state) => filled($state) ? 'primary' : 'gray')
                    ->badge(fn ($state) => filled($state))
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Buka Link' : '-')
                    ->url(fn ($state) => filled($state) ? (str_starts_with($state, 'http') ? $state : asset('storage/' . $state)) : null, shouldOpenInNewTab: true),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageLetters::route('/')];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
