<?php

namespace App\Filament\Staff\Resources\PenempatanSarprasResource\Pages;

use App\Filament\Staff\Resources\PenempatanSarprasResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePenempatanSarpras extends CreateRecord
{
    protected static string $resource = PenempatanSarprasResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        if (auth()->check() && !empty(auth()->user()->institution_id)) {
            $data['institution_id'] = auth()->user()->institution_id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->recalculateTotals();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
