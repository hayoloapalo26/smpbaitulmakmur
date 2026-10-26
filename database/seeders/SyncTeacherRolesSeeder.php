<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class SyncTeacherRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan role sudah ada (fallback)
        $roles = [
            'kepala_sekolah', 'waka_kesiswaan', 'guru_alquran', 'guru_mapel', 
            'sarpras', 'humas', 'tata_usaha', 'waka_kurikulum'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // 1. Dapatkan semua user yang memiliki jabatan
        $users = User::whereNotNull('jabatan')->get();

        foreach ($users as $user) {
            $jabatan = strtolower($user->jabatan);
            $assignedRoles = [];

            // 2. Logic "Routing" / Mapping Jabatan ke Spatie Role
            if (str_contains($jabatan, 'kepala sekolah')) {
                $assignedRoles[] = 'kepala_sekolah';
            }
            if (str_contains($jabatan, 'waka kesiswaan')) {
                $assignedRoles[] = 'waka_kesiswaan';
            }
            if (str_contains($jabatan, 'waka kurikulum')) {
                $assignedRoles[] = 'waka_kurikulum';
            }
            if (str_contains($jabatan, 'waka humas') || str_contains($jabatan, 'humas')) {
                $assignedRoles[] = 'humas';
            }
            if (str_contains($jabatan, 'sarpras')) {
                $assignedRoles[] = 'sarpras';
            }
            if (str_contains($jabatan, 'madin') || str_contains($jabatan, 'alquran')) {
                $assignedRoles[] = 'guru_alquran';
            }
            if (str_contains($jabatan, 'tata usaha') || str_contains($jabatan, 'tu')) {
                $assignedRoles[] = 'tata_usaha';
            }
            if (str_contains($jabatan, 'guru') || str_contains($jabatan, 'wali kelas')) {
                $assignedRoles[] = 'guru_mapel';
                
                if (str_contains($jabatan, 'wali kelas')) {
                    $assignedRoles[] = 'wali_kelas';
                }
            }

            // Jika ada role yang cocok, assign (timpa role lama)
            if (!empty($assignedRoles)) {
                $user->syncRoles(array_unique($assignedRoles));
            }
        }
    }
}
