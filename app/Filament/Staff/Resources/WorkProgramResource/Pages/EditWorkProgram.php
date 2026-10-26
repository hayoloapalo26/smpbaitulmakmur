<?php

namespace App\Filament\Staff\Resources\WorkProgramResource\Pages;

use App\Filament\Staff\Resources\WorkProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkProgram extends EditRecord
{
    protected static string $resource = WorkProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
