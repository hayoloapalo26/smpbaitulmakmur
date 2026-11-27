<?php

namespace App\Filament\Staff\Resources\ParentUserResource\Pages;

use App\Filament\Staff\Resources\ParentUserResource;
use App\Models\StudentGuardian;
use Filament\Resources\Pages\CreateRecord;

class CreateParentUser extends CreateRecord
{
    protected static string $resource = ParentUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['has_account'] = true;
        if (empty($data['institution_id'])) {
            $data['institution_id'] = auth()->user()?->institution_id ?: 2;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->assignRole('orang_tua');
        $guardian = $this->record->ensureGuardianRecord();

        $studentIds = (array) ($this->data['students'] ?? []);
        if (!empty($studentIds)) {
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
