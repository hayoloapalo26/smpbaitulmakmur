<?php

namespace App\Policies;

use App\Models\StudentAchievement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StudentAchievementPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan', 'wali_kelas', 'tata_usaha', 'bk']);
    }

    public function view(User $user, StudentAchievement $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan', 'wali_kelas', 'tata_usaha', 'bk']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function update(User $user, StudentAchievement $model): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function delete(User $user, StudentAchievement $model): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }
}