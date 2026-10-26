<?php

namespace App\Filament\Staff\Resources\InventoryResource\Pages;

use App\Filament\Staff\Resources\InventoryResource;
use App\Filament\Staff\Resources\InventoryResource\Widgets\InventoryStatsOverviewWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventories extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Catat Inventaris Baru')
                ->icon('heroicon-m-plus')
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'tata_usaha', 'superadmin'])),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InventoryStatsOverviewWidget::class,
        ];
    }
}
