<?php

namespace App\Filament\Staff\Resources\TeacherProfileResource\Pages;

use App\Filament\Staff\Resources\TeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditTeacherProfile extends EditRecord
{
    protected static string $resource = TeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $hasRealEmail = !empty($data['email']) && !str_contains($data['email'], '@placeholder.local');
        $hasRealUsername = !empty($data['username']) && !str_starts_with($data['username'], 'placeholder');

        if ($hasRealEmail && $hasRealUsername) {
            $data['has_account'] = true;
            $data['is_active'] = $data['is_active'] ?? true;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }
}
