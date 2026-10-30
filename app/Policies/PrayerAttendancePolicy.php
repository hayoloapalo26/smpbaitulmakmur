<?php

namespace App\Policies;

use App\Models\PrayerAttendance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PrayerAttendancePolicy
{
    private function isPiketOrWaka(User $user): bool
    {
        if ($user->hasRole(['waka_kesiswaan', 'wali_kelas'])) return true;
        // Check if user is piket today
        return $user->isPiketToday();


    }

    public function viewAny(User $user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $this->isPiketOrWaka($user);
    }

    public function view(User $user, PrayerAttendance $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $this->isPiketOrWaka($user);
    }

    public function create(User $user): bool
    {
        return $this->isPiketOrWaka($user);
    }

    public function update(User $user, PrayerAttendance $model): bool
    {
        return $this->isPiketOrWaka($user);
    }

    public function delete(User $user, PrayerAttendance $model): bool
    {
        return $this->isPiketOrWaka($user);
    }
}