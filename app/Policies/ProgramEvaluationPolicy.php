<?php

namespace App\Policies;

use App\Models\ProgramEvaluation;
use App\Models\User;

class ProgramEvaluationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'kepala_sekolah',
            'superadmin',
            'waka_kurikulum',
            'waka_kesiswaan',
            'sarpras',
            'humas',
            'bk',
            'wali_kelas',
            'guru_kelas',
            'ketua_yayasan',
            'pembina_yayasan',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProgramEvaluation $model): bool
    {
        if ($user->hasAnyRole(['kepala_sekolah', 'superadmin', 'ketua_yayasan', 'pembina_yayasan'])) {
            return true;
        }

        // Isolasi divisi
        return match ($model->divisi) {
            'Kurikulum' => $user->hasRole('waka_kurikulum'),
            'Kesiswaan' => $user->hasRole('waka_kesiswaan'),
            'Sarpras' => $user->hasRole('sarpras'),
            'Humas' => $user->hasRole('humas'),
            'Bimbingan Konseling' => $user->hasRole('bk'),
            'Wali Kelas' => $user->hasAnyRole(['wali_kelas', 'guru_kelas']),
            default => $user->id === $model->user_id,
        };
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'kepala_sekolah',
            'superadmin',
            'waka_kurikulum',
            'waka_kesiswaan',
            'sarpras',
            'humas',
            'bk',
            'wali_kelas',
            'guru_kelas',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProgramEvaluation $model): bool
    {
        if ($user->hasAnyRole(['kepala_sekolah', 'superadmin'])) {
            return true;
        }

        // User pembuat atau pemegang peran divisinya bisa update jika belum disetujui
        if ($user->id === $model->user_id) {
            return true;
        }

        return match ($model->divisi) {
            'Kurikulum' => $user->hasRole('waka_kurikulum'),
            'Kesiswaan' => $user->hasRole('waka_kesiswaan'),
            'Sarpras' => $user->hasRole('sarpras'),
            'Humas' => $user->hasRole('humas'),
            'Bimbingan Konseling' => $user->hasRole('bk'),
            'Wali Kelas' => $user->hasAnyRole(['wali_kelas', 'guru_kelas']),
            default => false,
        };
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProgramEvaluation $model): bool
    {
        return $user->hasAnyRole(['kepala_sekolah', 'superadmin']) || ($user->id === $model->user_id && $model->status !== 'Disetujui KS');
    }
}
