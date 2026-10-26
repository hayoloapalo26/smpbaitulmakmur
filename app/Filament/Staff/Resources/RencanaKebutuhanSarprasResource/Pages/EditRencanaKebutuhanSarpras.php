<?php

namespace App\Filament\Staff\Resources\RencanaKebutuhanSarprasResource\Pages;

use App\Filament\Staff\Resources\RencanaKebutuhanSarprasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRencanaKebutuhanSarpras extends EditRecord
{
    protected static string $resource = RencanaKebutuhanSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
