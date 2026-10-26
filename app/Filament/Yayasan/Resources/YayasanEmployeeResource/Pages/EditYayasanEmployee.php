<?php

namespace App\Filament\Yayasan\Resources\YayasanEmployeeResource\Pages;

use App\Filament\Yayasan\Resources\YayasanEmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYayasanEmployee extends EditRecord
{
    protected static string $resource = YayasanEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
