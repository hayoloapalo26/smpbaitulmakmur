<?php

namespace App\Filament\Staff\Resources\YayasanProgramResource\Pages;

use App\Filament\Staff\Resources\YayasanProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListYayasanPrograms extends ListRecords
{
    protected static string $resource = YayasanProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
