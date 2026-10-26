<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah']);
    }

    public function view(User $user, Classroom $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['waka_kurikulum', 'superadmin']);
    }

    public function update(User $user, Classroom $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Classroom $model): bool
    {
        return $this->create($user);
    }
}