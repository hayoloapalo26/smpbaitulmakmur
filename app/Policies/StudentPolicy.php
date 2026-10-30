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
        return $user->hasAnyRole(array_merge([
            'superadmin',
            // Semua Role Lembaga
            'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'tata_usaha',
            'wali_kelas', 'guru_kelas', 'guru_mapel', 'guru_piket', 'guru_ekstrakurikuler',
            'koordinator_madin', 'bk', 'sarpras', 'humas', 'bendahara', 'pegawai_keuangan',
            // Semua Role Yayasan
            'yayasan', 'pembina_yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan',
            'keuangan_yayasan', 'pegawai_keuangan_yayasan', 'tim_pengembang_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'penjamin_mutu_yayasan'
        ], User::QURAN_TEACHER_ROLES));
    }

    public function view($user, $model): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool
    {
        return $user->hasAnyRole(['superadmin', 'tata_usaha', 'waka_kesiswaan', 'kepala_sekolah']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'tata_usaha', 'waka_kesiswaan', 'wali_kelas', 'kepala_sekolah']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'tata_usaha']);
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
