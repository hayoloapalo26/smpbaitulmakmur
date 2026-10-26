<?php

namespace App\Filament\Yayasan\Resources\InstitutionResource\Pages;

use App\Filament\Yayasan\Resources\InstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInstitution extends ViewRecord
{
    protected static string $resource = InstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
