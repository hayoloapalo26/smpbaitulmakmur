<?php

namespace App\Filament\Yayasan\Resources\TeacherObservationResource\Pages;

use App\Filament\Yayasan\Resources\TeacherObservationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeacherObservation extends EditRecord
{
    protected static string $resource = TeacherObservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
