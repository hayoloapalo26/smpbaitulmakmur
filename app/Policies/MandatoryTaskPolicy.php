<?php

namespace App\Policies;

use App\Models\MandatoryTask;
use App\Models\User;

class MandatoryTaskPolicy
{
    /**
     * Fitur To-Do List Wajib telah dinonaktifkan untuk lembaga sesuai arahan kebijakan.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, MandatoryTask $model): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MandatoryTask $model): bool
    {
        return false;
    }

    public function delete(User $user, MandatoryTask $model): bool
    {
        return false;
    }
}
