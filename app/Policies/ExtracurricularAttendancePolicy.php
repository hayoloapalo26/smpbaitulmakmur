<?php

namespace App\Policies;

use App\Models\ExtracurricularAttendance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExtracurricularAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan', 'guru_ekstrakurikuler']);
    }

    public function view(User $user, ExtracurricularAttendance $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan', 'guru_ekstrakurikuler']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['waka_kesiswaan', 'guru_ekstrakurikuler']);
    }

    public function update(User $user, ExtracurricularAttendance $model): bool
    {
        return $user->hasRole(['waka_kesiswaan', 'guru_ekstrakurikuler']);
    }

    public function delete(User $user, ExtracurricularAttendance $model): bool
    {
        return $user->hasRole(['waka_kesiswaan', 'guru_ekstrakurikuler']);
    }
}