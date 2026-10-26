<?php

namespace App\Filament\Staff\Resources\TeacherProfileResource\Pages;

use App\Filament\Staff\Resources\TeacherProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTeacherProfile extends CreateRecord
{
    protected static string $resource = TeacherProfileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $slugName = Str::slug($data['name'] ?? 'guru');
        $random = rand(1000, 9999);
        $data['username'] = $data['username'] ?? ($slugName . $random);
        $data['email'] = $data['email'] ?? ($slugName . $random . '@placeholder.local');
        $data['password'] = bcrypt(\Illuminate\Support\Str::random(16));
        $data['has_account'] = false; // Menunggu pembuatan akun oleh Superadmin
        $data['is_active'] = false;
        
        if (empty($data['institution_id']) && auth()->user()->institution_id) {
            $data['institution_id'] = auth()->user()->institution_id;
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Automatically assign 'guru_mapel' or 'karyawan' role by default so they can login.
        // Superadmin can adjust this later.
        $this->record->assignRole('guru_mapel');
    }
}
