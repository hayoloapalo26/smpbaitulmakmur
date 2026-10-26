<?php

namespace App\Filament\Staff\Resources\RencanaKebutuhanSarprasResource\Pages;

use App\Filament\Staff\Resources\RencanaKebutuhanSarprasResource;
use App\Filament\Staff\Resources\RencanaKebutuhanSarprasResource\Widgets\RencanaKebutuhanStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRencanaKebutuhanSarpras extends ListRecords
{
    protected static string $resource = RencanaKebutuhanSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Susun Rencana Kebutuhan')
                ->icon('heroicon-m-plus')
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RencanaKebutuhanStatsWidget::class,
        ];
    }
}
