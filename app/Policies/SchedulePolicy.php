<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_merge(['superadmin', 'kepala_sekolah', 'waka_kurikulum', 'guru_mapel', 'wali_kelas', 'tata_usaha'], User::QURAN_TEACHER_ROLES));
    }

    public function view(User $user, Schedule $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'waka_kurikulum']);
    }

    public function update(User $user, Schedule $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'waka_kurikulum']);
    }

    public function delete(User $user, Schedule $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'waka_kurikulum']);
    }
}
