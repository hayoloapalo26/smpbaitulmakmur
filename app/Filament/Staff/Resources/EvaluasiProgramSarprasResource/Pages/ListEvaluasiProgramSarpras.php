<?php

namespace App\Filament\Staff\Resources\EvaluasiProgramSarprasResource\Pages;

use App\Filament\Staff\Resources\EvaluasiProgramSarprasResource;
use App\Filament\Staff\Resources\EvaluasiProgramSarprasResource\Widgets\EvaluasiProgramSarprasStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvaluasiProgramSarpras extends ListRecords
{
    protected static string $resource = EvaluasiProgramSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Evaluasi Program')
                ->icon('heroicon-m-plus')
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EvaluasiProgramSarprasStatsWidget::class,
        ];
    }
}
