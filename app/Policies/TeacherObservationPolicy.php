<?php

namespace App\Policies;

use App\Models\TeacherObservation;
use App\Models\User;

class TeacherObservationPolicy
{
    protected array $observerRoles = [
        'superadmin',
        'tim_pengembang_penjamin_mutu_yayasan',
        'pengembang_penjamin_mutu',
        'tim_pengembang_yayasan',
        'ketua_yayasan',
        'pembina_yayasan',
        'sekretaris_yayasan',
    ];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_merge(
            $this->observerRoles,
            ['kepala_sekolah', 'guru_mapel', 'wali_kelas', 'guru_kelas', 'waka_kurikulum'],
            User::QURAN_TEACHER_ROLES
        ));
    }

    public function view(User $user, TeacherObservation $model): bool
    {
        if ($user->hasAnyRole($this->observerRoles) || $user->hasRole('kepala_sekolah')) {
            return true;
        }

        return $user->id === $model->teacher_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole($this->observerRoles);
    }

    public function update(User $user, TeacherObservation $model): bool
    {
        return $user->hasAnyRole($this->observerRoles);
    }

    public function delete(User $user, TeacherObservation $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu', 'ketua_yayasan']);
    }
}
