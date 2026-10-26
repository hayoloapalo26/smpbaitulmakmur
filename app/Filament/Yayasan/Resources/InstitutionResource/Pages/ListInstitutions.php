<?php

namespace App\Filament\Yayasan\Resources\InstitutionResource\Pages;

use App\Filament\Yayasan\Resources\InstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstitutions extends ListRecords
{
    protected static string $resource = InstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
