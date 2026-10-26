<?php

namespace App\Filament\Staff\Resources\TeachingMaterialResource\Pages;

use App\Filament\Staff\Resources\TeachingMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTeachingMaterials extends ManageRecords
{
    protected static string $resource = TeachingMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
