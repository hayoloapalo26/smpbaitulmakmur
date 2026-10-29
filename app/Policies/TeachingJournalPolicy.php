<?php

namespace App\Policies;

use App\Models\TeachingJournal;
use App\Models\User;

class TeachingJournalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(array_merge(['superadmin', 'kepala_sekolah', 'waka_kurikulum', 'guru_mapel', 'wali_kelas', 'tata_usaha'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public function view(User $user, TeachingJournal $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(array_merge(['superadmin', 'waka_kurikulum', 'guru_mapel', 'wali_kelas'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public function update(User $user, TeachingJournal $model): bool
    {
        return $user->hasAnyRole(array_merge(['superadmin', 'waka_kurikulum', 'guru_mapel', 'wali_kelas'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public function delete(User $user, TeachingJournal $model): bool
    {
        return $user->hasAnyRole(array_merge(['superadmin', 'waka_kurikulum', 'guru_mapel', 'wali_kelas'], \App\Models\User::QURAN_TEACHER_ROLES));
    }
}
