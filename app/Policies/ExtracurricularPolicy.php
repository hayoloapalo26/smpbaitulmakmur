<?php

namespace App\Policies;

use App\Models\Extracurricular;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ExtracurricularPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan', 'guru_ekstrakurikuler']);
    }

    public function view(User $user, Extracurricular $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan', 'guru_ekstrakurikuler']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function update(User $user, Extracurricular $model): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function delete(User $user, Extracurricular $model): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }
}