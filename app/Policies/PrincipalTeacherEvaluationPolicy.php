<?php

namespace App\Policies;

use App\Models\PrincipalTeacherEvaluation;
use App\Models\User;

class PrincipalTeacherEvaluationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_merge([
            'superadmin',
            'kepala_sekolah',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
            'waka_kurikulum',
            'guru_mapel',
            'wali_kelas',
            'guru_ekstrakurikuler',
        ], User::QURAN_TEACHER_ROLES));
    }

    public function view(User $user, PrincipalTeacherEvaluation $model): bool
    {
        if ($user->hasAnyRole(['superadmin', 'kepala_sekolah', 'ketua_yayasan', 'tim_pengembang_penjamin_mutu_yayasan', 'pengembang_penjamin_mutu'])) {
            return true;
        }

        return $model->teacher_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'kepala_sekolah']);
    }

    public function update(User $user, PrincipalTeacherEvaluation $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'kepala_sekolah']);
    }

    public function delete(User $user, PrincipalTeacherEvaluation $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'kepala_sekolah']);
    }
}
