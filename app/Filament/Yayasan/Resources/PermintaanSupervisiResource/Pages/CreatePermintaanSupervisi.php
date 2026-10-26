<?php

namespace App\Filament\Yayasan\Resources\PermintaanSupervisiResource\Pages;

use App\Filament\Yayasan\Resources\PermintaanSupervisiResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePermintaanSupervisi extends CreateRecord
{
    protected static string $resource = PermintaanSupervisiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
