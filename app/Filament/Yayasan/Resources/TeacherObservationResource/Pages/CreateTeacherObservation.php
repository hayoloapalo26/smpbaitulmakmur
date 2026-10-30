<?php

namespace App\Filament\Yayasan\Resources\TeacherObservationResource\Pages;

use App\Filament\Yayasan\Resources\TeacherObservationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacherObservation extends CreateRecord
{
    protected static string $resource = TeacherObservationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
