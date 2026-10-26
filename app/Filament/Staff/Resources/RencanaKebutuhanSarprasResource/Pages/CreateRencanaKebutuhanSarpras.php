<?php

namespace App\Filament\Staff\Resources\RencanaKebutuhanSarprasResource\Pages;

use App\Filament\Staff\Resources\RencanaKebutuhanSarprasResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRencanaKebutuhanSarpras extends CreateRecord
{
    protected static string $resource = RencanaKebutuhanSarprasResource::class;

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
