<?php

namespace App\Policies;

use App\Models\SubjectAttendance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubjectAttendancePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kurikulum', 'guru_mapel', 'guru_alquran']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, $model): bool
    {
        return $user->hasRole(['waka_kurikulum', 'guru_mapel', 'guru_alquran']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return $user->hasRole(['waka_kurikulum', 'guru_mapel', 'guru_alquran']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, $model): bool
    {
        return $user->hasRole(['waka_kurikulum', 'guru_mapel', 'guru_alquran']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, $model): bool
    {
        return $user->hasRole(['waka_kurikulum', 'guru_mapel', 'guru_alquran']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, $model): bool
    {
        return $user->hasRole(['waka_kurikulum', 'guru_mapel', 'guru_alquran']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, $model): bool
    {
        return $user->hasRole(['waka_kurikulum', 'guru_mapel', 'guru_alquran']);
    }
}
