<?php

namespace App\Filament\Staff\Resources\TeacherAbsenceReportResource\Pages;

use App\Filament\Staff\Resources\TeacherAbsenceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTeacherAbsenceReports extends ManageRecords
{
    protected static string $resource = TeacherAbsenceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
