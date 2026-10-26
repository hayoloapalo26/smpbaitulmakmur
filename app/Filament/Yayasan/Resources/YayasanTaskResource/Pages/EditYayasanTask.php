<?php

namespace App\Filament\Yayasan\Resources\YayasanTaskResource\Pages;

use App\Filament\Yayasan\Resources\YayasanTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYayasanTask extends EditRecord
{
    protected static string $resource = YayasanTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
