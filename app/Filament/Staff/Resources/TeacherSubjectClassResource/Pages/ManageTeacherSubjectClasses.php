<?php

namespace App\Filament\Staff\Resources\TeacherSubjectClassResource\Pages;

use App\Filament\Staff\Resources\TeacherSubjectClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTeacherSubjectClasses extends ManageRecords
{
    protected static string $resource = TeacherSubjectClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
