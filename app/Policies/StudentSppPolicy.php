<?php

namespace App\Policies;

use App\Models\StudentSpp;
use App\Models\User;

class StudentSppPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'pegawai_keuangan_yayasan',
            'pegawai_keuangan',
            'superadmin',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'yayasan',
        ]);
    }

    public function view(User $user, StudentSpp $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'pegawai_keuangan_yayasan',
            'pegawai_keuangan',
            'superadmin',
        ]);
    }

    public function update(User $user, StudentSpp $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, StudentSpp $model): bool
    {
        return $this->create($user);
    }
}
