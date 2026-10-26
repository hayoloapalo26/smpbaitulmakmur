<?php

namespace App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource\Pages;

use App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource;
use App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource\Widgets\PerawatanBerkalaStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPerawatanBerkalaSarpras extends ListRecords
{
    protected static string $resource = PerawatanBerkalaSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Laporan Perawatan')
                ->icon('heroicon-m-plus')
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'tata_usaha', 'superadmin'])),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PerawatanBerkalaStatsWidget::class,
        ];
    }
}
