<?php

namespace App\Filament\Staff\Resources\ProgramSarprasResource\Pages;

use App\Filament\Staff\Resources\ProgramSarprasResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageProgramSarpras extends ManageRecords
{
    protected static string $resource = ProgramSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
