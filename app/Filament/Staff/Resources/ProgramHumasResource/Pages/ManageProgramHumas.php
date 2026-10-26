<?php

namespace App\Filament\Staff\Resources\ProgramHumasResource\Pages;

use App\Filament\Staff\Resources\ProgramHumasResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageProgramHumas extends ManageRecords
{
    protected static string $resource = ProgramHumasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
