<?php

namespace App\Policies;

use App\Models\BkReport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BkReportPolicy
{
    public function viewAny($user): bool {
        if ($user->hasRole('kepala_sekolah')) return true; return $user->hasRole(['bk', 'kepala_sekolah', 'waka_kesiswaan', 'wali_kelas']); }
    public function view($user, $model): bool { return $user->hasRole(['bk', 'kepala_sekolah', 'waka_kesiswaan', 'wali_kelas']); }
    public function create($user): bool { return false; /* auto-created by observer */ }
    public function update($user, $model): bool { return $user->hasRole('bk'); }
    public function delete($user, $model): bool { return $user->hasRole('bk'); }
}
