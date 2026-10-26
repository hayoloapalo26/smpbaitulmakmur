<?php

namespace App\Policies;

use App\Models\AlumniTracer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AlumniTracerPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan', 'tata_usaha', 'bk', 'wali_kelas']);
    }

    public function view(User $user, AlumniTracer $model): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['waka_kesiswaan', 'tata_usaha', 'bk', 'wali_kelas']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function update(User $user, AlumniTracer $model): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }

    public function delete(User $user, AlumniTracer $model): bool
    {
        return $user->hasRole(['waka_kesiswaan']);
    }
}