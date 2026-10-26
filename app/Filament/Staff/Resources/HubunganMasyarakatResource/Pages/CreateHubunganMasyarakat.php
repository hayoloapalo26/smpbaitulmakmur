<?php

namespace App\Filament\Staff\Resources\HubunganMasyarakatResource\Pages;

use App\Filament\Staff\Resources\HubunganMasyarakatResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHubunganMasyarakat extends CreateRecord
{
    protected static string $resource = HubunganMasyarakatResource::class;

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
