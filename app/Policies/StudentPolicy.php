<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool
    {
        return $user->hasAnyRole([
            'yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan', 'pembina_yayasan', 'pegawai_keuangan_yayasan', 'pegawai_keuangan',
            'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'tata_usaha', 'wali_kelas', 'guru_mapel', 'guru_alquran', 'bk'
        ]);
    }

    public function view($user, $model): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return $user->hasRole(['tata_usaha', 'waka_kesiswaan']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, $model): bool
    {
        return $user->hasRole(['tata_usaha', 'waka_kesiswaan', 'wali_kelas']);
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
