<?php

namespace App\Filament\Yayasan\Resources\StudentResource\Pages;

use App\Filament\Yayasan\Resources\StudentResource;
use Filament\Resources\Pages\ManageRecords;

class ManageStudents extends ManageRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
