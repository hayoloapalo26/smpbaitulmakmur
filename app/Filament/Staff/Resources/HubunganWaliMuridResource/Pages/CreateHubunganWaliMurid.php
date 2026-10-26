<?php

namespace App\Filament\Staff\Resources\HubunganWaliMuridResource\Pages;

use App\Filament\Staff\Resources\HubunganWaliMuridResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHubunganWaliMurid extends CreateRecord
{
    protected static string $resource = HubunganWaliMuridResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        if (auth()->user()?->institution_id) {
            $data['institution_id'] = auth()->user()->institution_id;
        }
        $data['status_persetujuan'] = 'Menunggu Persetujuan';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
