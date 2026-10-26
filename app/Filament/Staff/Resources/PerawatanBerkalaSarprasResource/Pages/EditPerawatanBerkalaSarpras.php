<?php

namespace App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource\Pages;

use App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPerawatanBerkalaSarpras extends EditRecord
{
    protected static string $resource = PerawatanBerkalaSarprasResource::class;

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
