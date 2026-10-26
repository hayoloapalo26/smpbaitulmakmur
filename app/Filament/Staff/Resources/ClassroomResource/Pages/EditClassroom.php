<?php

namespace App\Filament\Staff\Resources\ClassroomResource\Pages;

use App\Filament\Staff\Resources\ClassroomResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassroom extends EditRecord
{
    protected static string $resource = ClassroomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        if ($record->wali_kelas_id) {
            $user = User::find($record->wali_kelas_id);
            if ($user && !$user->hasRole('wali_kelas')) {
                $user->assignRole('wali_kelas');
            }
        }
    }
}