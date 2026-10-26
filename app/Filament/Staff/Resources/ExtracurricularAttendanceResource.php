<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\ExtracurricularAttendanceResource\Pages;
use App\Filament\Staff\Resources\ExtracurricularAttendanceResource\RelationManagers;
use App\Models\ExtracurricularAttendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExtracurricularAttendanceResource extends Resource
{
public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'superadmin']);
    }

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = ExtracurricularAttendance::class;

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
            'index' => Pages\ManageExtracurricularAttendances::route('/'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return static::getModelLabel();
    }
}
