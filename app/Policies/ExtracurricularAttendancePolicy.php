<?php

namespace App\Policies;

use App\Models\ExtracurricularAttendance;
use App\Models\User;

class ExtracurricularAttendancePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']);
    }

    public function view(User $user, ExtracurricularAttendance $model): bool
    {
        return $user->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'superadmin']);
    }

    public function update(User $user, ExtracurricularAttendance $model): bool
    {
        return $user->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'superadmin']);
    }

    public function delete(User $user, ExtracurricularAttendance $model): bool
    {
        return $user->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'superadmin']);
    }
}
