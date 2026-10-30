<?php

namespace App\Filament\Staff\Resources\TeacherProfileResource\Pages;

use App\Filament\Staff\Resources\TeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTeacherProfile extends CreateRecord
{
    protected static string $resource = TeacherProfileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $hasRealEmail = !empty($data['email']) && !str_contains($data['email'], '@placeholder.local');
        $hasRealUsername = !empty($data['username']) && !str_starts_with($data['username'], 'placeholder');

        if ($hasRealEmail || $hasRealUsername) {
            $slugName = Str::slug($data['name'] ?? 'guru');
            $data['username'] = $hasRealUsername ? $data['username'] : ($slugName . rand(100, 999));
            $data['email'] = $hasRealEmail ? $data['email'] : ($slugName . rand(100, 999) . '@guru.local');
            $data['password'] = !empty($data['password']) ? Hash::make($data['password']) : Hash::make('guru123');
            $data['has_account'] = true;
            $data['is_active'] = $data['is_active'] ?? true;
        } else {
            $slugName = Str::slug($data['name'] ?? 'guru');
            $random = rand(1000, 9999);
            $data['username'] = $slugName . $random;
            $data['email'] = $slugName . $random . '@placeholder.local';
            $data['password'] = Hash::make(Str::random(16));
            $data['has_account'] = false;
            $data['is_active'] = false;
        }

        if (empty($data['institution_id']) && auth()->user()->institution_id) {
            $data['institution_id'] = auth()->user()->institution_id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->roles()->count() === 0) {
            $this->record->assignRole('guru_mapel');
        }

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
