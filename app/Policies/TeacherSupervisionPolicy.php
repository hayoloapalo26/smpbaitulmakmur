<?php

namespace App\Policies;

use App\Models\TeacherSupervision;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeacherSupervisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_merge(['superadmin', 'kepala_sekolah', 'guru_mapel', 'wali_kelas', 'waka_kurikulum', 'ketua_yayasan', 'tim_pengembang_penjamin_mutu_yayasan'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public function view(User $user, TeacherSupervision $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(array_merge(['superadmin', 'guru_mapel', 'wali_kelas', 'waka_kurikulum'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public function update(User $user, TeacherSupervision $model): bool
    {
        return true;
    }

    public function delete(User $user, TeacherSupervision $model): bool
    {
        return $user->hasAnyRole(array_merge(['superadmin', 'guru_mapel', 'wali_kelas', 'waka_kurikulum'], \App\Models\User::QURAN_TEACHER_ROLES));
    }
}