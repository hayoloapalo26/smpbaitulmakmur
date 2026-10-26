<?php

namespace App\Filament\Yayasan\Resources\PermintaanUkgResource\Pages;

use App\Filament\Yayasan\Resources\PermintaanUkgResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePermintaanUkg extends CreateRecord
{
    protected static string $resource = PermintaanUkgResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
