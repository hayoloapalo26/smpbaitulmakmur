<?php

namespace App\Filament\Staff\Resources\ProgramKesiswaanResource\Pages;

use App\Filament\Staff\Resources\ProgramKesiswaanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProgramKesiswaan extends EditRecord
{
    protected static string $resource = ProgramKesiswaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
