<?php

namespace App\Filament\Staff\Resources\OptionalTaskResource\Pages;

use App\Filament\Staff\Resources\OptionalTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOptionalTask extends CreateRecord
{
    protected static string $resource = OptionalTaskResource::class;

    protected static ?string $title = 'Buat Tugas Insidentil Baru';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['institution_id'])) {
            $data['institution_id'] = auth()->user()->institution_id;
        }
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
