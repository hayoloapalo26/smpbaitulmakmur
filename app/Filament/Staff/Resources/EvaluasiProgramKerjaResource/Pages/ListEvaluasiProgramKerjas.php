<?php

namespace App\Filament\Staff\Resources\EvaluasiProgramKerjaResource\Pages;

use App\Filament\Staff\Resources\EvaluasiProgramKerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvaluasiProgramKerjas extends ListRecords
{
    protected static string $resource = EvaluasiProgramKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Evaluasi Program')
                ->icon('heroicon-m-plus')
                ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
        ];
    }
}
