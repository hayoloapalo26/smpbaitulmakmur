<?php

namespace App\Policies;

use App\Models\MeetingMinute;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MeetingMinutePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'tata_usaha', 'wali_kelas', 'guru_mapel', 'guru_alquran']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, $model): bool
    {
        return $user->hasRole(['kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'tata_usaha', 'wali_kelas', 'guru_mapel', 'guru_alquran']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return $user->hasRole(['tata_usaha']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, $model): bool
    {
        return $user->hasRole(['tata_usaha']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, $model): bool
    {
        return $user->hasRole(['tata_usaha']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, $model): bool
    {
        return $user->hasRole(['tata_usaha']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, $model): bool
    {
        return $user->hasRole(['tata_usaha']);
    }
}
