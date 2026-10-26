<?php

namespace App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource\Pages;

use App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePerawatanBerkalaSarpras extends CreateRecord
{
    protected static string $resource = PerawatanBerkalaSarprasResource::class;

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
