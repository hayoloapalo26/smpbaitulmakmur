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
        if (!auth()->user()?->hasRole('superadmin')) {
            unset($data['has_account'], $data['is_active'], $data['password'], $data['email'], $data['username']);
            return $data;
        }

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

    protected function afterSave(): void
    {
        if ($this->record->institution_id && \Illuminate\Support\Facades\Schema::hasTable('institution_user')) {
            try {
                \Illuminate\Support\Facades\DB::table('institution_user')->updateOrInsert(
                    ['user_id' => $this->record->id, 'institution_id' => $this->record->institution_id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            } catch (\Throwable $e) {}
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
