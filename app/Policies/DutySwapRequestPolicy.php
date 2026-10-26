<?php

namespace App\Policies;

use App\Models\DutySwapRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DutySwapRequestPolicy
{
    private function isPiketOrWaka(User $user): bool
    {
        if ($user->hasRole(['waka_kesiswaan'])) return true;
        // Check if user is piket today
        return \App\Models\DutySchedule::where('teacher_id', $user->id)
            ->whereDate('tanggal', \Carbon\Carbon::today())
            ->exists();
    }

    public function viewAny(User $user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $this->isPiketOrWaka($user);
    }

    public function view(User $user, DutySwapRequest $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $this->isPiketOrWaka($user);
    }

    public function create(User $user): bool
    {
        return $this->isPiketOrWaka($user);
    }

    public function update(User $user, DutySwapRequest $model): bool
    {
        return $this->isPiketOrWaka($user);
    }

    public function delete(User $user, DutySwapRequest $model): bool
    {
        return $this->isPiketOrWaka($user);
    }
}