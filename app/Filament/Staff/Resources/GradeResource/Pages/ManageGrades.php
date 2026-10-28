<?php

namespace App\Filament\Staff\Resources\GradeResource\Pages;

use App\Filament\Staff\Resources\GradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageGrades extends ManageRecords
{
    protected static string $resource = GradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('rumah_nilai')
                ->label('🏠 Buka Lembar Input Nilai Per Mapel (Batch Grid)')
                ->color('success')
                ->icon('heroicon-o-academic-cap')
                ->url(fn () => url('/staff/input-nilai-mapel')),
            Actions\CreateAction::make()
                ->label('Input Nilai Satuan'),
        ];
    }
}
