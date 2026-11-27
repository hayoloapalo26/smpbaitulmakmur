<?php

namespace App\Filament\Staff\Resources\ParentUserResource\Pages;

use App\Filament\Staff\Resources\ParentUserResource;
use App\Models\StudentGuardian;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParentUser extends EditRecord
{
    protected static string $resource = ParentUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['students'] = $this->record->students()->pluck('students.id')->toArray();
        if (empty($data['students']) && $this->record->guardian_id) {
            $data['students'] = StudentGuardian::where('guardian_id', $this->record->guardian_id)
                ->pluck('student_id')
                ->toArray();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if (!$this->record->hasRole('orang_tua')) {
            $this->record->assignRole('orang_tua');
        }

        $guardian = $this->record->ensureGuardianRecord();

        if (isset($this->data['students'])) {
            $studentIds = (array) ($this->data['students'] ?? []);

            StudentGuardian::where('guardian_id', $guardian->id)
                ->orWhere('user_id', $this->record->id)
                ->whereNotIn('student_id', $studentIds)
                ->delete();

            foreach ($studentIds as $sid) {
                StudentGuardian::updateOrCreate(
                    [
                        'student_id'  => $sid,
                        'guardian_id' => $guardian->id,
                    ],
                    [
                        'user_id'  => $this->record->id,
                        'relation' => 'Orang Tua',
                    ]
                );
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
