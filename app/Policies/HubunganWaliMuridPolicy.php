<?php

namespace App\Policies;

use App\Models\HubunganWaliMurid;
use App\Models\User;

class HubunganWaliMuridPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'humas',
            'kepala_sekolah',
            'superadmin',
            'yayasan',
            'ketua_yayasan',
            'wakil_ketua_yayasan',
            'sekretaris_yayasan',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, HubunganWaliMurid $model): bool
    {
        return $user->hasAnyRole([
            'humas',
            'kepala_sekolah',
            'superadmin',
            'yayasan',
            'ketua_yayasan',
            'wakil_ketua_yayasan',
            'sekretaris_yayasan',
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['humas', 'superadmin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, HubunganWaliMurid $model): bool
    {
        return $user->hasAnyRole(['humas', 'kepala_sekolah', 'superadmin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, HubunganWaliMurid $model): bool
    {
        return $user->hasAnyRole(['humas', 'superadmin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, HubunganWaliMurid $model): bool
    {
        return $user->hasAnyRole(['humas', 'superadmin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, HubunganWaliMurid $model): bool
    {
        return $user->hasRole('superadmin');
    }
}
