<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\DocumentArchiveResource\Pages;
use App\Models\DocumentArchive;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentArchiveResource extends Resource
{
public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['tata_usaha', 'superadmin', 'kepala_sekolah']);
    }

    protected static ?string $model = DocumentArchive::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Administrasi & Data Induk';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Arsip Elektronik';
    protected static ?string $recordTitleAttribute = 'judul';


    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kategori')->required(),
                Forms\Components\TextInput::make('judul')->required(),
                Forms\Components\FileUpload::make('file')->label('File Arsip')->required(),
                Forms\Components\DatePicker::make('tanggal')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')->date(),
                Tables\Columns\TextColumn::make('kategori')->searchable(),
                Tables\Columns\TextColumn::make('judul')->searchable(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageDocumentArchives::route('/')];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
