<?php

namespace App\Filament\Staff\Resources\InventoryResource\Pages;

use App\Filament\Staff\Resources\InventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInventory extends ViewRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'tata_usaha', 'superadmin'])),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
        ];
    }
}
