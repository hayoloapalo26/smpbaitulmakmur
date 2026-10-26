<?php

namespace App\Policies;

use App\Models\PenempatanSarpras;
use App\Models\User;

class PenempatanSarprasPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'sarpras',
            'tata_usaha',
            'kepala_sekolah',
            'superadmin',
            'yayasan',
            'ketua_yayasan',
            'wakil_ketua_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PenempatanSarpras $model): bool
    {
        return $user->hasAnyRole([
            'sarpras',
            'tata_usaha',
            'kepala_sekolah',
            'superadmin',
            'yayasan',
            'ketua_yayasan',
            'wakil_ketua_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['sarpras', 'tata_usaha', 'superadmin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PenempatanSarpras $model): bool
    {
        return $user->hasAnyRole(['sarpras', 'tata_usaha', 'superadmin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PenempatanSarpras $model): bool
    {
        return $user->hasAnyRole(['sarpras', 'superadmin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PenempatanSarpras $model): bool
    {
        return $user->hasAnyRole(['sarpras', 'superadmin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PenempatanSarpras $model): bool
    {
        return $user->hasRole('superadmin');
    }
}
