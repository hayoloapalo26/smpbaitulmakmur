<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\YayasanProgramResource\Pages;
use App\Models\YayasanProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class YayasanProgramResource extends Resource
{
    protected static ?string $model = YayasanProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Program Kerja';
    protected static ?string $navigationGroup = 'Yayasan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Program')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('institutions')
                    ->label('Tujuan Lembaga')
                    ->multiple()
                    ->relationship('institutions', 'name')
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi Program')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama Program')->searchable(),
                Tables\Columns\TextColumn::make('institutions.name')
                    ->label('Lembaga Tujuan')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListYayasanPrograms::route('/'),
            'create' => Pages\CreateYayasanProgram::route('/create'),
            'edit' => Pages\EditYayasanProgram::route('/{record}/edit'),
        ];
    }
}
