<?php

namespace App\Policies;

use App\Models\TeacherAttendance;
use App\Models\User;

class TeacherAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum', 'tata_usaha', 'guru_mapel', 'guru_alquran', 'wali_kelas']);
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
