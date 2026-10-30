<?php

namespace App\Policies;

use App\Models\TeacherLeaveRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeacherLeaveRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return $user->hasRole(array_merge(['waka_kurikulum', 'kepala_sekolah', 'guru_mapel', 'wali_kelas', 'tata_usaha', 'superadmin'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, $model): bool
    {
        return $user->hasRole(array_merge(['waka_kurikulum', 'kepala_sekolah', 'guru_mapel', 'wali_kelas', 'tata_usaha', 'superadmin'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        if ($user->hasRole('kepala_sekolah')) {
            return false;
        }

        return $user->hasRole(array_merge(['waka_kurikulum', 'guru_mapel', 'wali_kelas', 'tata_usaha', 'superadmin'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) {
            return false;
        }

        return $user->hasRole(array_merge(['waka_kurikulum', 'guru_mapel', 'wali_kelas', 'tata_usaha', 'superadmin'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) {
            return false;
        }

        return $user->hasRole(array_merge(['waka_kurikulum', 'guru_mapel', 'wali_kelas', 'superadmin'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) {
            return false;
        }

        return $user->hasRole(array_merge(['waka_kurikulum', 'guru_mapel', 'wali_kelas', 'superadmin'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) {
            return false;
        }

        return $user->hasRole(array_merge(['waka_kurikulum', 'guru_mapel', 'wali_kelas', 'superadmin'], \App\Models\User::QURAN_TEACHER_ROLES));
    }
}
