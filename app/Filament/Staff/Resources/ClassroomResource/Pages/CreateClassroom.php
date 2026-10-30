<?php

namespace App\Filament\Staff\Resources\ClassroomResource\Pages;

use App\Filament\Staff\Resources\ClassroomResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClassroom extends CreateRecord
{
    protected static string $resource = ClassroomResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->hasUser() && !auth()->user()->hasRole('superadmin')) {
            $data['institution_id'] = auth()->user()->institution_id;
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        ClassroomResource::syncTeacherRoles($this->record);
    }
}