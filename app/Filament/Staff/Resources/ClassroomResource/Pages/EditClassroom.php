<?php

namespace App\Filament\Staff\Resources\ClassroomResource\Pages;

use App\Filament\Staff\Resources\ClassroomResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassroom extends EditRecord
{
    protected static string $resource = ClassroomResource::class;

    protected ?int $oldWaliKelasId = null;

    protected function beforeSave(): void
    {
        $this->oldWaliKelasId = $this->record->wali_kelas_id;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    if ($this->record->wali_kelas_id) {
                        ClassroomResource::syncTeacherRoles($this->record, $this->record->wali_kelas_id);
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        ClassroomResource::syncTeacherRoles($this->record, $this->oldWaliKelasId);
    }
}