<?php

namespace App\Filament\Staff\Resources\SubjectAttendanceResource\Pages;

use App\Filament\Staff\Resources\SubjectAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSubjectAttendances extends ManageRecords
{
    protected static string $resource = SubjectAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
