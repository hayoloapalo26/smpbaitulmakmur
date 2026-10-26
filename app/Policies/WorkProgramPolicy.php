<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkProgram;
use Illuminate\Auth\Access\Response;

class WorkProgramPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $user->hasRole(['kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'sarpras', 'humas']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkProgram $workProgram): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        
        // Hanya bisa melihat program divisinya sendiri
        return $workProgram->penanggung_jawab_id === $user->id || $workProgram->divisi === $this->getDivisiFromRole($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'sarpras', 'humas']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkProgram $workProgram): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        
        // Bisa update divisinya sendiri, asalkan belum disetujui (atau update laporan)
        return $workProgram->penanggung_jawab_id === $user->id || $workProgram->divisi === $this->getDivisiFromRole($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkProgram $workProgram): bool
    {
        if ($user->hasRole('kepala_sekolah')) return true;
        return $workProgram->penanggung_jawab_id === $user->id && $workProgram->status_pengajuan === 'draft';
    }

    private function getDivisiFromRole(User $user): ?string
    {
        if ($user->hasRole('waka_kurikulum')) return 'waka_kurikulum';
        if ($user->hasRole('waka_kesiswaan')) return 'waka_kesiswaan';
        if ($user->hasRole('sarpras')) return 'sarpras';
        if ($user->hasRole('humas')) return 'humas';
        if ($user->hasRole('kepala_sekolah')) return 'kepala_sekolah';
        return null;
    }
}
