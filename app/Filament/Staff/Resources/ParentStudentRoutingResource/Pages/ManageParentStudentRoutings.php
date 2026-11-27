<?php

namespace App\Filament\Staff\Resources\ParentStudentRoutingResource\Pages;

use App\Filament\Staff\Resources\ParentStudentRoutingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageParentStudentRoutings extends ManageRecords
{
    protected static string $resource = ParentStudentRoutingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Pembuatan akun dikelola oleh Superadmin di Manajemen Akun
        ];
    }
}
