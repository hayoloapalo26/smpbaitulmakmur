<?php

namespace App\Filament\Staff\Resources\ProgramKepsekResource\Pages;

use App\Filament\Staff\Resources\ProgramKepsekResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageProgramKepseks extends ManageRecords
{
    protected static string $resource = ProgramKepsekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
