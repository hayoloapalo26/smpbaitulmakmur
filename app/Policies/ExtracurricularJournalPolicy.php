<?php

namespace App\Policies;

use App\Models\ExtracurricularJournal;
use App\Models\User;

class ExtracurricularJournalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['guru_ekstrakurikuler', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin']);
    }

    public function view(User $user, ExtracurricularJournal $journal): bool
    {
        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return true;
        }

        if ($user->hasRole('guru_ekstrakurikuler')) {
            return $journal->pembina_id === $user->id || $journal->extracurricular?->pembina_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Khusus Guru Ekstrakurikuler & Superadmin yang menginput jurnal mengajar eskul
        return $user->hasAnyRole(['guru_ekstrakurikuler', 'superadmin']);
    }

    public function update(User $user, ExtracurricularJournal $journal): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Kesiswaan hanya view dan memberi catatan/verifikasi via Action, tidak mengubah isi materi guru eskul
        if ($user->hasRole('guru_ekstrakurikuler')) {
            return ($journal->pembina_id === $user->id || $journal->extracurricular?->pembina_id === $user->id) 
                && $journal->status_verifikasi !== 'diterima';
        }

        return false;
    }

    public function delete(User $user, ExtracurricularJournal $journal): bool
    {
        if ($user->hasRole('superadmin')) {
            return true;
        }

        if ($user->hasRole('guru_ekstrakurikuler')) {
            return ($journal->pembina_id === $user->id || $journal->extracurricular?->pembina_id === $user->id)
                && $journal->status_verifikasi !== 'diterima';
        }

        return false;
    }
}
