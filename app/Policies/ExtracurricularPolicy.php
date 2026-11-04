<?php

namespace App\Policies;

use App\Models\Extracurricular;
use App\Models\User;

class ExtracurricularPolicy
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
        return $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'guru_ekstrakurikuler', 'superadmin']);
    }

    public function view(User $user, Extracurricular $model): bool
    {
        return $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'guru_ekstrakurikuler', 'superadmin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }

    public function update(User $user, Extracurricular $model): bool
    {
        return $user->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }

    public function delete(User $user, Extracurricular $model): bool
    {
        return $user->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }
}
