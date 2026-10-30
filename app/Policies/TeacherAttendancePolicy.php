<?php

namespace App\Policies;

use App\Models\TeacherAttendance;
use App\Models\User;

class TeacherAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_merge(['superadmin', 'kepala_sekolah', 'waka_kurikulum', 'tata_usaha', 'guru_mapel', 'wali_kelas'], User::QURAN_TEACHER_ROLES));
    }

    public function view(User $user, TeacherAttendance $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'waka_kurikulum']);
    }

    public function update(User $user, TeacherAttendance $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'waka_kurikulum']);
    }

    public function delete(User $user, TeacherAttendance $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'waka_kurikulum']);
    }
}
