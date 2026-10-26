<?php

namespace App\Policies;

use App\Models\AttributeViolation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttributeViolationPolicy
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

    public function view(User $user, AttributeViolation $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $this->isPiketOrWaka($user);
    }

    public function create(User $user): bool
    {
        return $this->isPiketOrWaka($user);
    }

    public function update(User $user, AttributeViolation $model): bool
    {
        return $this->isPiketOrWaka($user);
    }

    public function delete(User $user, AttributeViolation $model): bool
    {
        return $this->isPiketOrWaka($user);
    }
}