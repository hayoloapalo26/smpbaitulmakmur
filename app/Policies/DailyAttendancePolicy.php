<?php

namespace App\Policies;

use App\Models\DailyAttendance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DailyAttendancePolicy
{
    private function isPiketOrWakaOrWali(User $user): bool
    {
        if ($user->hasRole(['waka_kesiswaan', 'wali_kelas'])) return true;
        // Check if user is piket today
        return \App\Models\DutySchedule::where('teacher_id', $user->id)
            ->whereDate('tanggal', \Carbon\Carbon::today())
            ->exists();
    }

    public function viewAny(User $user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $this->isPiketOrWakaOrWali($user);
    }

    public function view(User $user, DailyAttendance $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $this->isPiketOrWakaOrWali($user);
    }

    public function create(User $user): bool
    {
        return $this->isPiketOrWakaOrWali($user);
    }

    public function update(User $user, DailyAttendance $model): bool
    {
        return $this->isPiketOrWakaOrWali($user);
    }

    public function delete(User $user, DailyAttendance $model): bool
    {
        return $this->isPiketOrWakaOrWali($user);
    }
}
