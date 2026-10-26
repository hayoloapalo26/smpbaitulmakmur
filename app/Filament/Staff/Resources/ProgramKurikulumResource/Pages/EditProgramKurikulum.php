<?php

namespace App\Filament\Staff\Resources\ProgramKurikulumResource\Pages;

use App\Filament\Staff\Resources\ProgramKurikulumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProgramKurikulum extends EditRecord
{
    protected static string $resource = ProgramKurikulumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
