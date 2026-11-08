<?php

namespace App\Policies;

use App\Models\Rppm;
use App\Models\User;

class RppmPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->isQuranTeacher()) {
            return false;
        }

        return $user->hasAnyRole([
            'superadmin',
            'kepala_sekolah',
            'waka_kurikulum',
            'guru_mapel',
            'guru_kelas',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     * Hanya guru yang bersangkutan sendiri, Waka Kurikulum, Kepala Sekolah, dan Yayasan/Superadmin yang bisa melihat.
     * Guru lain tidak boleh melihat.
     */
    public function view(User $user, Rppm $rppm): bool
    {
        if ($user->isQuranTeacher()) {
            return false;
        }

        if ($user->hasAnyRole([
            'superadmin',
            'kepala_sekolah',
            'waka_kurikulum',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
        ])) {
            return true;
        }

        return (int) $rppm->guru_id === (int) $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->isQuranTeacher()) {
            return false;
        }

        return $user->hasAnyRole(['superadmin', 'waka_kurikulum', 'kepala_sekolah', 'guru_mapel', 'guru_kelas']);
    }

    /**
     * Determine whether the user can update the model.
     * Hanya guru yang bersangkutan sendiri, Waka Kurikulum, dan Kepala Sekolah yang dapat mengedit/meng-acc.
     */
    public function update(User $user, Rppm $rppm): bool
    {
        if ($user->isQuranTeacher()) {
            return false;
        }

        if ($user->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum'])) {
            return true;
        }

        return (int) $rppm->guru_id === (int) $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Rppm $rppm): bool
    {
        if ($user->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum'])) {
            return true;
        }

        return (int) $rppm->guru_id === (int) $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Rppm $rppm): bool
    {
        return $this->delete($user, $rppm);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Rppm $rppm): bool
    {
        return $this->delete($user, $rppm);
    }
}
