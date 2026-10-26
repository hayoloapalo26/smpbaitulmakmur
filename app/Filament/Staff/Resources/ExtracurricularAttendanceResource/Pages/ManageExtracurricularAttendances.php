<?php

namespace App\Filament\Staff\Resources\ExtracurricularAttendanceResource\Pages;

use App\Filament\Staff\Resources\ExtracurricularAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageExtracurricularAttendances extends ManageRecords
{
    protected static string $resource = ExtracurricularAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
