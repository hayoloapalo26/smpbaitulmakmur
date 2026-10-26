<?php

namespace App\Policies;

use App\Models\YayasanProgram;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class YayasanProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Everyone can view yayasan programs if it's in their nav
    }

    public function view(User $user, YayasanProgram $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['yayasan', 'ketua_yayasan', 'wakil_ketua_yayasan', 'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan', 'pembina_yayasan', 'pegawai_keuangan_yayasan', 'pegawai_keuangan', 'superadmin']);
    }

    public function update(User $user, YayasanProgram $model): bool
    {
        return true; // Everyone can view yayasan programs if it's in their nav
    }

    public function delete(User $user, YayasanProgram $model): bool
    {
        return true; // Everyone can view yayasan programs if it's in their nav
    }
}