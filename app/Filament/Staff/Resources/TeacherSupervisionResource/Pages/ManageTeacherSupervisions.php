<?php

namespace App\Filament\Staff\Resources\TeacherSupervisionResource\Pages;

use App\Filament\Staff\Resources\TeacherSupervisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTeacherSupervisions extends ManageRecords
{
    protected static string $resource = TeacherSupervisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
