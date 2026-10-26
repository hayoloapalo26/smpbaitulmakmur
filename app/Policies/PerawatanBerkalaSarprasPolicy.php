<?php

namespace App\Policies;

use App\Models\PerawatanBerkalaSarpras;
use App\Models\User;

class PerawatanBerkalaSarprasPolicy
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
    public function view(User $user, PerawatanBerkalaSarpras $model): bool
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
    public function update(User $user, PerawatanBerkalaSarpras $model): bool
    {
        return $user->hasAnyRole(['sarpras', 'tata_usaha', 'kepala_sekolah', 'superadmin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PerawatanBerkalaSarpras $model): bool
    {
        return $user->hasAnyRole(['sarpras', 'superadmin']);
    }

    /**
     * Determine whether the user can approve/reject the model.
     */
    public function approve(User $user, PerawatanBerkalaSarpras $model): bool
    {
        return $user->hasAnyRole(['kepala_sekolah', 'superadmin']);
    }
}
