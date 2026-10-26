<?php

namespace App\Policies;

use App\Models\OsisProgram;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OsisProgramPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function view(User $user, OsisProgram $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function update(User $user, OsisProgram $model): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function delete(User $user, OsisProgram $model): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }
}