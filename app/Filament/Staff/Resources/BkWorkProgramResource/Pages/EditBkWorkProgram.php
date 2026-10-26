<?php

namespace App\Filament\Staff\Resources\BkWorkProgramResource\Pages;

use App\Filament\Staff\Resources\BkWorkProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBkWorkProgram extends EditRecord
{
    protected static string $resource = BkWorkProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
