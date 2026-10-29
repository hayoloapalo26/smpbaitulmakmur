<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GradePolicy
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
        if ($user->hasRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum', 'koordinator_madin'])) return true;
        return $user->hasAnyRole(['guru_mapel', 'guru_kelas', 'guru_alquran', 'wali_kelas']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, $model): bool
    {
        if ($user->hasRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum', 'koordinator_madin'])) return true;
        return $user->hasAnyRole(['guru_mapel', 'guru_kelas', 'guru_alquran', 'wali_kelas']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return $user->hasAnyRole(['guru_mapel', 'guru_kelas', 'guru_alquran', 'koordinator_madin', 'waka_kurikulum', 'superadmin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, $model): bool
    {
        return $user->hasAnyRole(['guru_mapel', 'guru_kelas', 'guru_alquran', 'koordinator_madin', 'waka_kurikulum', 'superadmin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, $model): bool
    {
        return $user->hasAnyRole(['guru_mapel', 'guru_kelas', 'guru_alquran', 'waka_kurikulum', 'superadmin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, $model): bool
    {
        return $user->hasRole(['guru_mapel']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, $model): bool
    {
        return $user->hasRole(['guru_mapel']);
    }
}
