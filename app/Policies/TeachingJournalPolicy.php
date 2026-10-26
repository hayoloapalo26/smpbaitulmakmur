<?php

namespace App\Policies;

use App\Models\TeachingJournal;
use App\Models\User;

class TeachingJournalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'kepala_sekolah', 'waka_kurikulum', 'guru_mapel', 'guru_alquran', 'wali_kelas', 'tata_usaha']);
    }

    public function view(User $user, TeachingJournal $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'waka_kurikulum', 'guru_mapel', 'guru_alquran', 'wali_kelas']);
    }

    public function update(User $user, TeachingJournal $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'waka_kurikulum', 'guru_mapel', 'guru_alquran', 'wali_kelas']);
    }

    public function delete(User $user, TeachingJournal $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'waka_kurikulum', 'guru_mapel', 'guru_alquran', 'wali_kelas']);
    }
}
