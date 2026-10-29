<?php

namespace App\Policies;

use App\Models\DutySchedule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DutySchedulePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('kepala_sekolah') || $user->hasRole('superadmin')) return true;
        return $user->hasRole(array_merge([
            'waka_kesiswaan', 'kepala_sekolah', 'guru_mapel', 
            'wali_kelas', 'guru_kelas', 'bk', 'guru_piket', 'tata_usaha'
        ], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public function view(User $user, DutySchedule $model): bool
    {
        if ($user->hasRole('kepala_sekolah') || $user->hasRole('superadmin')) return true;
        return $user->hasRole(array_merge([
            'waka_kesiswaan', 'kepala_sekolah', 'guru_mapel', 
            'wali_kelas', 'guru_kelas', 'bk', 'guru_piket', 'tata_usaha'
        ], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['waka_kesiswaan', 'superadmin']);
    }

    public function update(User $user, DutySchedule $model): bool
    {
        return $user->hasRole(['waka_kesiswaan', 'superadmin']);
    }

    public function delete(User $user, DutySchedule $model): bool
    {
        return $user->hasRole(['waka_kesiswaan', 'superadmin']);
    }
}
