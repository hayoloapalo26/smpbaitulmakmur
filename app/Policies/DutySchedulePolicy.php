<?php

namespace App\Policies;

use App\Models\DutySchedule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DutySchedulePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan', 'kepala_sekolah', 'guru_mapel', 'guru_alquran', 'wali_kelas']);
    }

    public function view(User $user, DutySchedule $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan', 'kepala_sekolah', 'guru_mapel', 'guru_alquran', 'wali_kelas']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function update(User $user, DutySchedule $model): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function delete(User $user, DutySchedule $model): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }
}
