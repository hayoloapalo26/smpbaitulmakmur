<?php

namespace App\Filament\Staff\Resources\DutyScheduleResource\Pages;

use App\Filament\Staff\Resources\DutyScheduleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDutySchedule extends CreateRecord
{
    protected static string $resource = DutyScheduleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
