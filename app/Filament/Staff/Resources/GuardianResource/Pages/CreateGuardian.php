<?php

namespace App\Filament\Staff\Resources\GuardianResource\Pages;

use App\Filament\Staff\Resources\GuardianResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuardian extends CreateRecord
{
    protected static string $resource = GuardianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['institution_id'])) {
            $data['institution_id'] = auth()->user()?->institution_id ?: 2;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
