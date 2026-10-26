<?php

namespace App\Filament\Yayasan\Resources;

use App\Filament\Yayasan\Resources\LabAssessmentResource\Pages;
use App\Models\LabAssessment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LabAssessmentResource extends Resource
{
    protected static ?string $model = LabAssessment::class;
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Penilaian Labkom';
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
            'index' => Pages\ListLabAssessments::route('/'),
            'create' => Pages\CreateLabAssessment::route('/create'),
            'edit' => Pages\EditLabAssessment::route('/{record}/edit'),
        ];
    }
}
