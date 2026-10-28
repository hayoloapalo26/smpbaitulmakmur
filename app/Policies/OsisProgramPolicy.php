<?php

namespace App\Policies;

use App\Models\OsisProgram;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OsisProgramPolicy
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
        return $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']);
    }

    public function view(User $user, OsisProgram $model): bool
    {
        return $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }

    public function update(User $user, OsisProgram $model): bool
    {
        return $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']);
    }

    public function delete(User $user, OsisProgram $model): bool
    {
        return $user->hasAnyRole(['waka_kesiswaan', 'superadmin']);
    }
}