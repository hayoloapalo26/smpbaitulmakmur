<?php

namespace App\Filament\Staff\Resources\StudentLeaveRequestResource\Pages;

use App\Filament\Staff\Resources\StudentLeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentLeaveRequests extends ManageRecords
{
    protected static string $resource = StudentLeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
