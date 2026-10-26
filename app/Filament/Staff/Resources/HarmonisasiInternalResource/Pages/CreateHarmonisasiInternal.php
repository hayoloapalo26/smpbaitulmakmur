<?php

namespace App\Filament\Staff\Resources\HarmonisasiInternalResource\Pages;

use App\Filament\Staff\Resources\HarmonisasiInternalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHarmonisasiInternal extends CreateRecord
{
    protected static string $resource = HarmonisasiInternalResource::class;

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
