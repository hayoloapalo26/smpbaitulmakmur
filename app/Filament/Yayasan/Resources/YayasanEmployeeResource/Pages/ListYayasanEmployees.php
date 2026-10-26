<?php

namespace App\Filament\Yayasan\Resources\YayasanEmployeeResource\Pages;

use App\Filament\Yayasan\Resources\YayasanEmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYayasanEmployees extends ListRecords
{
    protected static string $resource = YayasanEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
