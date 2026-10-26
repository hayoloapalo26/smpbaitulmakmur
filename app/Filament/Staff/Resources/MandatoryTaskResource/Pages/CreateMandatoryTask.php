<?php

namespace App\Filament\Staff\Resources\MandatoryTaskResource\Pages;

use App\Filament\Staff\Resources\MandatoryTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMandatoryTask extends CreateRecord
{
    protected static string $resource = MandatoryTaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['institution_id'])) {
            $data['institution_id'] = auth()->user()->institution_id;
        }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}