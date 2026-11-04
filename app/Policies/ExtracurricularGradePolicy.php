<?php

namespace App\Policies;

use App\Models\ExtracurricularGrade;
use App\Models\User;

class ExtracurricularGradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']);
    }

    public function view(User $user, ExtracurricularGrade $grade): bool
    {
        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return true;
        }

        if ($user->hasRole('guru_ekstrakurikuler')) {
            return $grade->pembina_id === $user->id || $grade->extracurricular?->pembina_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['guru_ekstrakurikuler', 'superadmin']);
    }

    public function update(User $user, ExtracurricularGrade $grade): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($user->hasRole('guru_ekstrakurikuler')) {
            return $grade->pembina_id === $user->id || $grade->extracurricular?->pembina_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, ExtracurricularGrade $grade): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($user->hasRole('guru_ekstrakurikuler')) {
            return $grade->pembina_id === $user->id || $grade->extracurricular?->pembina_id === $user->id;
        }

        return false;
    }
}
