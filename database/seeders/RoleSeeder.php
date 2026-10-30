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
            'pengembang_penjamin_mutu',
            'tim_pengembang_yayasan',
            'pegawai_keuangan_yayasan',
            'pegawai_keuangan',
            'kepala_sekolah',
            'waka_kurikulum',
            'waka_kesiswaan',
            'tata_usaha',
            'wali_kelas',
            'guru_mapel',
            'guru_alquran',
            'guru_quran_yanbua',
            'guru_quran_tahsin',
            'guru_quran_tahfidz_dasar',
            'guru_quran_tahfidz_lanjutan',
            'koordinator_madin',
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
