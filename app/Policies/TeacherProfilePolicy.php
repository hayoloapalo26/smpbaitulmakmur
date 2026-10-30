<?php

namespace App\Policies;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeacherProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_merge([
            'kepala_sekolah', 'yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 
            'sekretaris_yayasan', 'pembina_yayasan', 'tim_pengembang_penjamin_mutu_yayasan', 'pegawai_keuangan_yayasan', 'pegawai_keuangan', 'tata_usaha', 
            'waka_kurikulum', 'waka_kesiswaan', 'sarpras', 'humas', 'bk', 'superadmin', 'guru_mapel', 'wali_kelas'
        ], User::QURAN_TEACHER_ROLES));
    }

    public function view(User $user, TeacherProfile $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'kepala_sekolah', 'tata_usaha']);
    }

    public function update(User $user, TeacherProfile $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, TeacherProfile $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'tata_usaha', 'kepala_sekolah']);
    }
}