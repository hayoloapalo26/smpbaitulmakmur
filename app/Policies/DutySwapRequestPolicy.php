<?php

namespace App\Policies;

use App\Models\DutySchedule;
use App\Models\DutySwapRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DutySwapRequestPolicy
{
    private function isAuthorized(User $user): bool
    {
        if ($user->hasRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return true;
        }

        return $user->hasAnyRole(array_merge([
            'guru_mapel', 'wali_kelas', 'guru_kelas', 'bk', 'guru_piket', 'tata_usaha'
        ], \App\Models\User::QURAN_TEACHER_ROLES)) || DutySchedule::where('teacher_id', $user->id)->exists();
    }

    public function viewAny(User $user): bool
    {
        return $this->isAuthorized($user);
    }

    public function view(User $user, DutySwapRequest $model): bool
    {
        if ($user->hasRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return true;
        }

        return $model->requester_id === $user->id || $model->substitute_teacher_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isAuthorized($user);
    }

    public function update(User $user, DutySwapRequest $model): bool
    {
        if ($user->hasRole(['waka_kesiswaan', 'superadmin'])) {
            return true;
        }

        return $model->requester_id === $user->id || $model->substitute_teacher_id === $user->id;
    }

    public function delete(User $user, DutySwapRequest $model): bool
    {
        if ($user->hasRole(['waka_kesiswaan', 'superadmin'])) {
            return true;
        }

        return $model->requester_id === $user->id && $model->status === 'diajukan';
    }
}
