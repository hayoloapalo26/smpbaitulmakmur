<?php

namespace App\Filament\Staff\Resources\OsisProgramResource\Pages;

use App\Filament\Staff\Resources\OsisProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageOsisPrograms extends ManageRecords
{
    protected static string $resource = OsisProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
