<?php

namespace App\Policies;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeacherProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'superadmin',
            'kepala_sekolah',
            'tata_usaha',
            'waka_kurikulum',
        ]);
    }

    public function view(User $user, TeacherProfile $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'superadmin',
            'kepala_sekolah',
            'tata_usaha',
            'waka_kurikulum',
        ]);
    }

    public function update(User $user, TeacherProfile $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, TeacherProfile $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'tata_usaha', 'kepala_sekolah']);
    }
}