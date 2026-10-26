<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum', 'guru_mapel', 'guru_alquran', 'wali_kelas', 'tata_usaha']);
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
