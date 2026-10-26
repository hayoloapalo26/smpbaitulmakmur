<?php

namespace App\Filament\Yayasan\Resources\YayasanTaskResource\Pages;

use App\Filament\Yayasan\Resources\YayasanTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYayasanTasks extends ListRecords
{
    protected static string $resource = YayasanTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
