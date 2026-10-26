<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\CanteenAssessmentResource\Pages;
use App\Models\CanteenAssessment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CanteenAssessmentResource extends Resource
{
    protected static ?string $model = CanteenAssessment::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Penilaian Kantin';
    protected static ?string $navigationGroup = 'Yayasan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('aspect_name')
                    ->label('Aspek Penilaian')
                    ->required(),
                Forms\Components\Toggle::make('is_completed')
                    ->label('Selesai?'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('aspect_name')->label('Aspek Penilaian')->searchable(),
                Tables\Columns\IconColumn::make('is_completed')->boolean()->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListCanteenAssessments::route('/'),
            'create' => Pages\CreateCanteenAssessment::route('/create'),
            'edit' => Pages\EditCanteenAssessment::route('/{record}/edit'),
        ];
    }
}
