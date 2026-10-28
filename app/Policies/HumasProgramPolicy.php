<?php

namespace App\Policies;

use App\Models\HumasProgram;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HumasProgramPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return $user->hasAnyRole(['kepala_sekolah', 'humas', 'superadmin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, $model): bool
    {
        return $user->hasAnyRole(['kepala_sekolah', 'humas', 'superadmin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return $user->hasAnyRole(['humas', 'superadmin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, $model): bool
    {
        return $user->hasAnyRole(['humas', 'kepala_sekolah', 'superadmin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, $model): bool
    {
        return $user->hasAnyRole(['humas', 'superadmin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, $model): bool
    {
        return $user->hasRole(['humas']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, $model): bool
    {
        return $user->hasRole(['humas']);
    }
}
