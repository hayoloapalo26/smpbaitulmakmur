<?php

namespace App\Filament\Yayasan\Resources\TeacherObservationResource\Pages;

use App\Filament\Yayasan\Resources\TeacherObservationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherObservations extends ListRecords
{
    protected static string $resource = TeacherObservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Observasi Guru'),
        ];
    }
}
