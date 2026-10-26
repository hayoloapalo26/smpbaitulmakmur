<?php

namespace App\Policies;

use App\Models\TodoTask;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TodoTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan', 'pembina_yayasan', 'pegawai_keuangan_yayasan', 'pegawai_keuangan', 'superadmin', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan']);
    }

    public function view(User $user, TodoTask $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, TodoTask $model): bool
    {
        return $user->id === $model->created_by || $user->hasRole('kepala_sekolah');
    }

    public function delete(User $user, TodoTask $model): bool
    {
        return $user->id === $model->created_by || $user->hasRole('kepala_sekolah');
    }
}