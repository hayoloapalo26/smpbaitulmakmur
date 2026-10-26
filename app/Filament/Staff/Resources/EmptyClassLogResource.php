<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\EmptyClassLogResource\Pages;
use App\Filament\Staff\Resources\EmptyClassLogResource\RelationManagers;
use App\Models\EmptyClassLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmptyClassLogResource extends Resource
{
public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['guru_mapel', 'waka_kurikulum', 'superadmin']);
    }

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = EmptyClassLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
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
            'index' => Pages\ManageEmptyClassLogs::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
