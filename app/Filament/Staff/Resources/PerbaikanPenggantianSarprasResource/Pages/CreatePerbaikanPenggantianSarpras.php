<?php

namespace App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource\Pages;

use App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePerbaikanPenggantianSarpras extends CreateRecord
{
    protected static string $resource = PerbaikanPenggantianSarprasResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        if (auth()->check() && !empty(auth()->user()->institution_id)) {
            $data['institution_id'] = auth()->user()->institution_id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
