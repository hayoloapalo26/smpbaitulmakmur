<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'yayasan',
            'ketua_yayasan',
            'wakil_ketua_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pegawai_keuangan_yayasan',
            'pegawai_keuangan',
            'kepala_sekolah',
            'waka_kurikulum',
            'waka_kesiswaan',
            'tata_usaha',
            'wali_kelas',
            'guru_mapel',
            'guru_alquran',
            'sarpras',
            'humas',
            'bk',
            'siswa',
            'orang_tua',
            'guru_ekstrakurikuler'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
