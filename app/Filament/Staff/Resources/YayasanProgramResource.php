<?php

namespace App\Filament\Staff\Resources;

use App\Filament\Staff\Resources\YayasanProgramResource\Pages;
use App\Models\YayasanProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class YayasanProgramResource extends Resource
{
    protected static ?string $model = YayasanProgram::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Program Yayasan';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $pluralModelLabel = 'Program Yayasan';
    protected static ?int $navigationSort = 14;

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    public static function getEloquentQuery(): Builder
    {
        $institutionId = auth()->user()?->institution_id;

        $query = parent::getEloquentQuery();
        if ($institutionId) {
            $query->with(['institutions' => function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId);
            }])->whereHas('institutions', function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId);
            });
        } else {
            $query->with('institutions');
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Program')
                    ->searchable()
                    ->description(fn ($record) => $record->description),
                Tables\Columns\IconColumn::make('is_completed')
                    ->label('Status Selesai')
                    ->getStateUsing(fn ($record) => $record->institutions->first()?->pivot->is_completed)
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\Action::make('update_progress')
                    ->label('Update Progres')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Toggle::make('is_completed')->label('Selesai Dilaksanakan?'),
                        Forms\Components\Textarea::make('notes')->label('Catatan Pelaksanaan'),
                    ])
                    ->fillForm(fn ($record) => [
                        'is_completed' => $record->institutions->first()?->pivot->is_completed,
                        'notes' => $record->institutions->first()?->pivot->notes,
                    ])
                    ->action(function ($record, array $data) {
                        $institutionId = auth()->user()?->institution_id ?? $record->institutions->first()?->id;
                        if ($institutionId) {
                            $record->institutions()->updateExistingPivot($institutionId, [
                                'is_completed' => $data['is_completed'] ?? false,
                                'notes' => $data['notes'],
                            ]);
                        }
                    }),
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
        ];
    }
}
