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
        return $user->hasAnyRole(array_merge(['guru_mapel', 'guru_kelas', 'wali_kelas'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, $model): bool
    {
        if ($user->hasRole(['kepala_sekolah', 'superadmin', 'waka_kurikulum', 'koordinator_madin'])) return true;
        return $user->hasAnyRole(array_merge(['guru_mapel', 'guru_kelas', 'wali_kelas'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($user->hasRole('waka_kurikulum')) {
            return true;
        }

        return $user->hasAnyRole(array_merge(['guru_mapel', 'guru_kelas', 'koordinator_madin'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, $model): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($user->hasRole('waka_kurikulum')) {
            return $user->teachesSubject($model->subject_id);
        }

        if ($user->hasAnyRole(array_merge(['guru_mapel', 'guru_kelas', 'koordinator_madin'], \App\Models\User::QURAN_TEACHER_ROLES))) {
            return $user->teachesSubject($model->subject_id) || $model->teacher_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, $model): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($user->hasRole('waka_kurikulum')) {
            return $user->teachesSubject($model->subject_id);
        }

        if ($user->hasAnyRole(array_merge(['guru_mapel', 'guru_kelas'], \App\Models\User::QURAN_TEACHER_ROLES))) {
            return $user->teachesSubject($model->subject_id) || $model->teacher_id === $user->id;
        }

        return false;
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
