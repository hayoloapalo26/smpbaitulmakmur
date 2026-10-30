<?php

namespace App\Policies;

use App\Models\OptionalTask;
use App\Models\User;

class OptionalTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['kepala_sekolah', 'superadmin', 'yayasan']);
    }

    public function view(User $user, OptionalTask $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }

    public function update(User $user, OptionalTask $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, OptionalTask $model): bool
    {
        return $this->create($user);
    }
}
