<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\Institution;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat semua akun role khusus SD Islam Baitul Makmur (Opsi B - Isolasi Penuh, Password: 'password').
     * Tidak menyentuh / merusak data SMP sama sekali.
     */
    public function up(): void
    {
        // 1. Dapatkan Institusi SD (ID = 4)
        $sdInstitution = Institution::withoutGlobalScopes()->where('id', 4)->first();
        if (!$sdInstitution) {
            $sdInstitution = Institution::withoutGlobalScopes()->where('slug', 'sd')->first();
        }
        $sdId = $sdInstitution ? $sdInstitution->id : 4;

        // 2. Pastikan seluruh Role Spatie tersedia di sistem
        $rolesToEnsure = [
            'kepala_sekolah',
            'waka_kurikulum',
            'waka_kesiswaan',
            'tata_usaha',
            'pegawai_keuangan',
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
        ];

        foreach ($rolesToEnsure as $rName) {
            Role::firstOrCreate(['name' => $rName, 'guard_name' => 'web']);
        }

        // 3. Password universal khusus akun SD: 'password'
        $passwordHash = Hash::make('password');

        // 4. Daftar Lengkap Seluruh Role Khusus SD
        $sdRoleUsers = [
            [
                'name' => 'Kepala Sekolah SD Islam Baitul Makmur',
                'email' => 'kepsek_sd@baitulmakmur.sch.id',
                'username' => 'kepsek_sd',
                'roles' => ['kepala_sekolah'],
            ],
            [
                'name' => 'Waka Kurikulum SD Islam Baitul Makmur',
                'email' => 'kurikulum_sd@baitulmakmur.sch.id',
                'username' => 'kurikulum_sd',
                'roles' => ['waka_kurikulum', 'guru_mapel'],
            ],
            [
                'name' => 'Waka Kesiswaan SD Islam Baitul Makmur',
                'email' => 'kesiswaan_sd@baitulmakmur.sch.id',
                'username' => 'kesiswaan_sd',
                'roles' => ['waka_kesiswaan', 'guru_mapel'],
            ],
            [
                'name' => 'Tata Usaha SD Islam Baitul Makmur',
                'email' => 'tu_sd@baitulmakmur.sch.id',
                'username' => 'tu_sd',
                'roles' => ['tata_usaha'],
            ],
            [
                'name' => 'Bendahara / Keuangan SD Islam Baitul Makmur',
                'email' => 'keuangan_sd@baitulmakmur.sch.id',
                'username' => 'keuangan_sd',
                'roles' => ['pegawai_keuangan'],
            ],
            [
                'name' => 'Koordinator Sarpras SD Islam Baitul Makmur',
                'email' => 'sarpras_sd@baitulmakmur.sch.id',
                'username' => 'sarpras_sd',
                'roles' => ['sarpras', 'guru_mapel'],
            ],
            [
                'name' => 'Koordinator Humas SD Islam Baitul Makmur',
                'email' => 'humas_sd@baitulmakmur.sch.id',
                'username' => 'humas_sd',
                'roles' => ['humas', 'guru_mapel'],
            ],
            [
                'name' => 'Guru Bimbingan Konseling SD Islam Baitul Makmur',
                'email' => 'bk_sd@baitulmakmur.sch.id',
                'username' => 'bk_sd',
                'roles' => ['bk'],
            ],
            [
                'name' => 'Wali Kelas SD Islam Baitul Makmur',
                'email' => 'walikelas_sd@baitulmakmur.sch.id',
                'username' => 'walikelas_sd',
                'roles' => ['wali_kelas', 'guru_kelas', 'guru_mapel'],
            ],
            [
                'name' => 'Guru Kelas SD Islam Baitul Makmur',
                'email' => 'gurukelas_sd@baitulmakmur.sch.id',
                'username' => 'gurukelas_sd',
                'roles' => ['guru_kelas', 'guru_mapel'],
            ],
            [
                'name' => 'Guru Mata Pelajaran SD Islam Baitul Makmur',
                'email' => 'gurumapel_sd@baitulmakmur.sch.id',
                'username' => 'gurumapel_sd',
                'roles' => ['guru_mapel'],
            ],
            [
                'name' => 'Guru Pengajar SD Islam Baitul Makmur',
                'email' => 'guru_sd@baitulmakmur.sch.id',
                'username' => 'guru_sd',
                'roles' => ['guru', 'guru_mapel'],
            ],
            [
                'name' => 'Guru Piket SD Islam Baitul Makmur',
                'email' => 'gurupiket_sd@baitulmakmur.sch.id',
                'username' => 'gurupiket_sd',
                'roles' => ['guru_piket'],
            ],
            [
                'name' => 'Guru Al-Qur\'an SD Islam Baitul Makmur',
                'email' => 'gurualquran_sd@baitulmakmur.sch.id',
                'username' => 'gurualquran_sd',
                'roles' => ['guru_alquran'],
            ],
            [
                'name' => 'Guru Al-Qur\'an Yanbu\'a SD Islam Baitul Makmur',
                'email' => 'yanbua_sd@baitulmakmur.sch.id',
                'username' => 'yanbua_sd',
                'roles' => ['guru_quran_yanbua', 'guru_alquran'],
            ],
            [
                'name' => 'Guru Al-Qur\'an Tahsin SD Islam Baitul Makmur',
                'email' => 'tahsin_sd@baitulmakmur.sch.id',
                'username' => 'tahsin_sd',
                'roles' => ['guru_quran_tahsin', 'guru_alquran'],
            ],
            [
                'name' => 'Guru Al-Qur\'an Tahfidz Dasar SD Islam Baitul Makmur',
                'email' => 'tahfidzdasar_sd@baitulmakmur.sch.id',
                'username' => 'tahfidzdasar_sd',
                'roles' => ['guru_quran_tahfidz_dasar', 'guru_alquran'],
            ],
            [
                'name' => 'Guru Al-Qur\'an Tahfidz Lanjutan SD Islam Baitul Makmur',
                'email' => 'tahfidzlanjutan_sd@baitulmakmur.sch.id',
                'username' => 'tahfidzlanjutan_sd',
                'roles' => ['guru_quran_tahfidz_lanjutan', 'guru_alquran'],
            ],
            [
                'name' => 'Koordinator Madin SD Islam Baitul Makmur',
                'email' => 'madin_sd@baitulmakmur.sch.id',
                'username' => 'madin_sd',
                'roles' => ['koordinator_madin', 'guru_alquran'],
            ],
            [
                'name' => 'Pembina Ekstrakurikuler SD Islam Baitul Makmur',
                'email' => 'ekskul_sd@baitulmakmur.sch.id',
                'username' => 'ekskul_sd',
                'roles' => ['guru_ekstrakurikuler'],
            ],
            [
                'name' => 'Pembina Kesiswaan SD Islam Baitul Makmur',
                'email' => 'pembina_sd@baitulmakmur.sch.id',
                'username' => 'pembina_sd',
                'roles' => ['pembina_osis'],
            ],
        ];

        foreach ($sdRoleUsers as $userData) {
            $user = User::withoutGlobalScopes()->where('email', $userData['email'])->first();

            if (!$user) {
                $user = User::withoutGlobalScopes()->create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'username' => $userData['username'],
                    'password' => $passwordHash,
                    'institution_id' => $sdId,
                    'is_active' => true,
                    'has_account' => true,
                ]);
            } else {
                // Pastikan password, nama, dan status selalu sinkron
                $user->update([
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'password' => $passwordHash,
                    'institution_id' => $sdId,
                    'is_active' => true,
                    'has_account' => true,
                ]);
            }

            // Sync roles
            $user->syncRoles($userData['roles']);

            // Sync pivot institution_user khusus SD (ID = 4)
            if (Schema::hasTable('institution_user')) {
                DB::table('institution_user')->updateOrInsert(
                    ['user_id' => $user->id, 'institution_id' => $sdId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // 5. Hubungkan Wali Kelas & Guru Quran SD ke Rombel SD masing-masing
        $waliKelasUser = User::withoutGlobalScopes()->where('email', 'walikelas_sd@baitulmakmur.sch.id')->first();
        if ($waliKelasUser) {
            Classroom::withoutGlobalScopes()
                ->where('institution_id', $sdId)
                ->where('name', 'Kelas 1')
                ->update(['wali_kelas_id' => $waliKelasUser->id]);
        }

        $yanbuaUser = User::withoutGlobalScopes()->where('email', 'yanbua_sd@baitulmakmur.sch.id')->first();
        if ($yanbuaUser) {
            Classroom::withoutGlobalScopes()
                ->where('institution_id', $sdId)
                ->where('name', 'Kelas Yanbua SD')
                ->update(['wali_kelas_id' => $yanbuaUser->id]);
        }

        $tahsinUser = User::withoutGlobalScopes()->where('email', 'tahsin_sd@baitulmakmur.sch.id')->first();
        if ($tahsinUser) {
            Classroom::withoutGlobalScopes()
                ->where('institution_id', $sdId)
                ->where('name', 'Kelas Tahsin SD')
                ->update(['wali_kelas_id' => $tahsinUser->id]);
        }

        $tahfidzDasarUser = User::withoutGlobalScopes()->where('email', 'tahfidzdasar_sd@baitulmakmur.sch.id')->first();
        if ($tahfidzDasarUser) {
            Classroom::withoutGlobalScopes()
                ->where('institution_id', $sdId)
                ->where('name', 'Kelas Tahfidz Dasar SD')
                ->update(['wali_kelas_id' => $tahfidzDasarUser->id]);
        }

        $tahfidzLanjutanUser = User::withoutGlobalScopes()->where('email', 'tahfidzlanjutan_sd@baitulmakmur.sch.id')->first();
        if ($tahfidzLanjutanUser) {
            Classroom::withoutGlobalScopes()
                ->where('institution_id', $sdId)
                ->where('name', 'Kelas Tahfidz Lanjutan SD')
                ->update(['wali_kelas_id' => $tahfidzLanjutanUser->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op rollback agar aman
    }
};
