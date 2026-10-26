<?php

namespace App\Filament\Staff\Resources\OptionalTaskResource\Pages;

use App\Filament\Staff\Resources\OptionalTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOptionalTask extends CreateRecord
{
    protected static string $resource = OptionalTaskResource::class;

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