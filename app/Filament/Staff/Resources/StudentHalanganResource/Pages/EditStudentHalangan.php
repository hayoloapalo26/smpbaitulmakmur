<?php

namespace App\Filament\Staff\Resources\StudentHalanganResource\Pages;

use App\Filament\Staff\Resources\StudentHalanganResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentHalangan extends EditRecord
{
    protected static string $resource = StudentHalanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
