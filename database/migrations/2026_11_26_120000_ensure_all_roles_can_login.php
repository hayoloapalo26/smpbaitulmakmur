<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\Institution;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Memastikan seluruh role di sistem (SD, SMP, Yayasan, Superadmin, Portal) memiliki akun aktif
     * yang 100% siap digunakan untuk login dengan password: 'password'.
     * Data dan progres SMP tetap 100% aman dan terisolasi tanpa konflik.
     */
    public function up(): void
    {
        // 1. Pastikan seluruh Role Spatie terdaftar
        $allRoles = [
            'superadmin',
            'yayasan',
            'ketua_yayasan',
            'wakil_ketua_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
            'tim_pengembang_yayasan',
            'pembina_yayasan',
            'pegawai_keuangan_yayasan',
            'pegawai_keuangan',
            'kepala_sekolah',
            'waka_kurikulum',
            'waka_kesiswaan',
            'tata_usaha',
            'sarpras',
            'humas',
            'bk',
            'wali_kelas',
            'guru_kelas',
            'guru_mapel',
            'guru',
            'guru_piket',
            'guru_alquran',
            'guru_quran_yanbua',
            'guru_quran_tahsin',
            'guru_quran_tahfidz_dasar',
            'guru_quran_tahfidz_lanjutan',
            'koordinator_madin',
            'guru_ekstrakurikuler',
            'pembina_osis',
            'siswa',
            'orang_tua',
        ];

        foreach ($allRoles as $rName) {
            Role::firstOrCreate(['name' => $rName, 'guard_name' => 'web']);
        }

        $passwordHash = Hash::make('password');

        // 2. Daftar Akun Pengujian Komprehensif Seluruh Role
        $accounts = [
            // --- SUPERADMIN ---
            [
                'name' => 'Super Administrator Sistem',
                'email' => 'superadmin@baitulmakmur.sch.id',
                'username' => 'superadmin',
                'institution_id' => 1,
                'roles' => ['superadmin'],
            ],

            // --- YAYASAN (ID = 1) ---
            [
                'name' => 'Pengurus Yayasan Baitul Makmur',
                'email' => 'yayasan@baitulmakmur.sch.id',
                'username' => 'yayasan',
                'institution_id' => 1,
                'roles' => ['yayasan'],
            ],
            [
                'name' => 'Ketua Yayasan Baitul Makmur',
                'email' => 'ketuayayasan@baitulmakmur.sch.id',
                'username' => 'ketuayayasan',
                'institution_id' => 1,
                'roles' => ['ketua_yayasan', 'yayasan'],
            ],
            [
                'name' => 'Tim Penjamin Mutu Yayasan',
                'email' => 'penjaminmutu@baitulmakmur.sch.id',
                'username' => 'penjaminmutu',
                'institution_id' => 1,
                'roles' => ['pengembang_penjamin_mutu', 'tim_pengembang_penjamin_mutu_yayasan'],
            ],
            [
                'name' => 'Pegawai Keuangan Yayasan',
                'email' => 'keuangan_yayasan@baitulmakmur.sch.id',
                'username' => 'keuangan_yayasan',
                'institution_id' => 1,
                'roles' => ['pegawai_keuangan_yayasan', 'pegawai_keuangan'],
            ],

            // --- SMP ISLAM BAITUL MAKMUR (ID = 2) ---
            [
                'name' => 'Kepala Sekolah SMP Islam Baitul Makmur',
                'email' => 'kepsek_smp@baitulmakmur.sch.id',
                'username' => 'kepsek_smp',
                'institution_id' => 2,
                'roles' => ['kepala_sekolah'],
            ],
            [
                'name' => 'Waka Kurikulum SMP Islam Baitul Makmur',
                'email' => 'kurikulum_smp@baitulmakmur.sch.id',
                'username' => 'kurikulum_smp',
                'institution_id' => 2,
                'roles' => ['waka_kurikulum', 'guru_mapel'],
            ],
            [
                'name' => 'Waka Kesiswaan SMP Islam Baitul Makmur',
                'email' => 'kesiswaan_smp@baitulmakmur.sch.id',
                'username' => 'kesiswaan_smp',
                'institution_id' => 2,
                'roles' => ['waka_kesiswaan', 'guru_mapel'],
            ],
            [
                'name' => 'Tata Usaha SMP Islam Baitul Makmur',
                'email' => 'tu_smp@baitulmakmur.sch.id',
                'username' => 'tu_smp',
                'institution_id' => 2,
                'roles' => ['tata_usaha'],
            ],
            [
                'name' => 'Guru Pengajar SMP Islam Baitul Makmur',
                'email' => 'guru_smp@baitulmakmur.sch.id',
                'username' => 'guru_smp',
                'institution_id' => 2,
                'roles' => ['guru', 'guru_mapel'],
            ],
            [
                'name' => 'Wali Kelas SMP Islam Baitul Makmur',
                'email' => 'walikelas_smp@baitulmakmur.sch.id',
                'username' => 'walikelas_smp',
                'institution_id' => 2,
                'roles' => ['wali_kelas', 'guru_mapel'],
            ],
            [
                'name' => 'Siswa SMP Islam Baitul Makmur',
                'email' => 'siswa_smp@baitulmakmur.sch.id',
                'username' => 'siswa_smp',
                'institution_id' => 2,
                'roles' => ['siswa'],
            ],
            [
                'name' => 'Wali Murid / Orang Tua SMP',
                'email' => 'ortu_smp@baitulmakmur.sch.id',
                'username' => 'ortu_smp',
                'institution_id' => 2,
                'roles' => ['orang_tua'],
            ],

            // --- PORTAL SD (ID = 4) ---
            [
                'name' => 'Siswa SD Islam Baitul Makmur',
                'email' => 'siswa_sd@baitulmakmur.sch.id',
                'username' => 'siswa_sd',
                'institution_id' => 4,
                'roles' => ['siswa'],
            ],
            [
                'name' => 'Wali Murid / Orang Tua SD',
                'email' => 'ortu_sd@baitulmakmur.sch.id',
                'username' => 'ortu_sd',
                'institution_id' => 4,
                'roles' => ['orang_tua'],
            ],
        ];

        foreach ($accounts as $acc) {
            $user = User::withoutGlobalScopes()->where('email', $acc['email'])->first();

            if (!$user) {
                $user = User::withoutGlobalScopes()->create([
                    'name' => $acc['name'],
                    'email' => $acc['email'],
                    'username' => $acc['username'],
                    'password' => $passwordHash,
                    'institution_id' => $acc['institution_id'],
                    'is_active' => true,
                    'has_account' => true,
                ]);
            } else {
                $user->update([
                    'name' => $acc['name'],
                    'username' => $acc['username'],
                    'password' => $passwordHash,
                    'institution_id' => $acc['institution_id'],
                    'is_active' => true,
                    'has_account' => true,
                ]);
            }

            // Sync role
            $user->syncRoles($acc['roles']);

            // Sync pivot institution_user
            if (Schema::hasTable('institution_user')) {
                DB::table('institution_user')->updateOrInsert(
                    ['user_id' => $user->id, 'institution_id' => $acc['institution_id']],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback
    }
};
