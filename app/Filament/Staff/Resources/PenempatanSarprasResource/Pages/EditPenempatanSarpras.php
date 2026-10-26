<?php

namespace App\Filament\Staff\Resources\PenempatanSarprasResource\Pages;

use App\Filament\Staff\Resources\PenempatanSarprasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPenempatanSarpras extends EditRecord
{
    protected static string $resource = PenempatanSarprasResource::class;

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
