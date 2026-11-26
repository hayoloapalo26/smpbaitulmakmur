<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\Institution;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\SchoolPillar;
use App\Models\User;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menginisialisasi Master Data & Rombel SD Islam Baitul Makmur (Opsi B - Isolasi Ketat Tanpa Konflik dengan SMP).
     */
    public function up(): void
    {
        // 1. Pastikan Institusi SD (ID = 4) terkonfigurasi dengan tepat
        $sdInstitution = Institution::withoutGlobalScopes()->where('id', 4)->first();
        if (!$sdInstitution) {
            $sdInstitution = Institution::withoutGlobalScopes()->where('slug', 'sd')->first();
        }
        if ($sdInstitution) {
            $sdInstitution->update([
                'name' => 'SD Islam Baitul Makmur',
                'slug' => 'sd',
            ]);
            $sdId = $sdInstitution->id;
        } else {
            $sdId = DB::table('institutions')->insertGetId([
                'id' => 4,
                'name' => 'SD Islam Baitul Makmur',
                'slug' => 'sd',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Buat Rombel Kelas Reguler & Quran SD (Tingkat 1 - 6)
        $sdClassrooms = [
            ['name' => 'Kelas 1', 'tingkat' => '1', 'tipe' => 'reguler'],
            ['name' => 'Kelas 2', 'tingkat' => '2', 'tipe' => 'reguler'],
            ['name' => 'Kelas 3', 'tingkat' => '3', 'tipe' => 'reguler'],
            ['name' => 'Kelas 4', 'tingkat' => '4', 'tipe' => 'reguler'],
            ['name' => 'Kelas 5', 'tingkat' => '5', 'tipe' => 'reguler'],
            ['name' => 'Kelas 6', 'tingkat' => '6', 'tipe' => 'reguler'],
            // Kelas Al-Qur'an SD
            ['name' => 'Kelas Yanbua SD', 'tingkat' => '1', 'tipe' => 'quran'],
            ['name' => 'Kelas Tahsin SD', 'tingkat' => '1', 'tipe' => 'quran'],
            ['name' => 'Kelas Tahfidz Dasar SD', 'tingkat' => '1', 'tipe' => 'quran'],
            ['name' => 'Kelas Tahfidz Lanjutan SD', 'tingkat' => '1', 'tipe' => 'quran'],
        ];

        foreach ($sdClassrooms as $cls) {
            $existing = Classroom::withoutGlobalScopes()
                ->where('institution_id', $sdId)
                ->where('name', $cls['name'])
                ->first();

            if (!$existing) {
                Classroom::withoutGlobalScopes()->create([
                    'institution_id' => $sdId,
                    'name' => $cls['name'],
                    'tingkat' => $cls['tingkat'],
                    'tipe' => $cls['tipe'],
                ]);
            }
        }

        // 3. Buat Master Mata Pelajaran Kurikulum SD (Terisolasi di institution_id = 4)
        $sdSubjects = [
            ['name' => 'Pendidikan Agama Islam dan Budi Pekerti', 'kode' => 'PAI-SD'],
            ['name' => 'Pendidikan Pancasila', 'kode' => 'PP-SD'],
            ['name' => 'Bahasa Indonesia', 'kode' => 'BIND-SD'],
            ['name' => 'Matematika', 'kode' => 'MTK-SD'],
            ['name' => 'Ilmu Pengetahuan Alam dan Sosial (IPAS)', 'kode' => 'IPAS-SD'],
            ['name' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK)', 'kode' => 'PJOK-SD'],
            ['name' => 'Seni Budaya & Prakarya', 'kode' => 'SBDP-SD'],
            ['name' => 'Bahasa Inggris', 'kode' => 'BING-SD'],
            ['name' => 'Bahasa Jawa', 'kode' => 'BJAW-SD'],
            ['name' => 'Al-Qur’an / Tahfidz', 'kode' => 'QRN-SD'],
        ];

        foreach ($sdSubjects as $sub) {
            $existingSub = Subject::withoutGlobalScopes()
                ->where('institution_id', $sdId)
                ->where(function ($q) use ($sub) {
                    $q->where('name', $sub['name'])->orWhere('kode', $sub['kode']);
                })
                ->first();

            if (!$existingSub) {
                Subject::withoutGlobalScopes()->create([
                    'institution_id' => $sdId,
                    'name' => $sub['name'],
                    'kode' => $sub['kode'],
                ]);
            }
        }

        // 4. Buat 9 Pilar Sekolah untuk SD (institution_id = 4)
        $pillars = [
            1 => 'Bidang Manajemen',
            2 => 'Mutu Lulusan',
            3 => 'Bidang Kurikulum dan Pembelajaran',
            4 => 'PTK (Pendidik dan Tenaga Kependidikan)',
            5 => 'Kesiswaan',
            6 => 'Sarpras (Sarana Prasarana)',
            7 => 'Keuangan',
            8 => 'Kehumasan',
            9 => 'Layanan Khusus Sekolah',
        ];

        foreach ($pillars as $order => $pName) {
            $existingPillar = SchoolPillar::withoutGlobalScopes()
                ->where('institution_id', $sdId)
                ->where('order', $order)
                ->first();

            if (!$existingPillar) {
                SchoolPillar::withoutGlobalScopes()->create([
                    'institution_id' => $sdId,
                    'order' => $order,
                    'name' => $pName,
                ]);
            }
        }

        // 5. Buat Akun Pengguna Default SD (Siap Digunakan)
        $rolesToEnsure = ['kepala_sekolah', 'guru', 'guru_mapel', 'tata_usaha', 'staff'];
        foreach ($rolesToEnsure as $rName) {
            Role::firstOrCreate(['name' => $rName, 'guard_name' => 'web']);
        }

        $defaultUsers = [
            [
                'name' => 'Kepala Sekolah SD Islam Baitul Makmur',
                'email' => 'kepsek_sd@baitulmakmur.sch.id',
                'username' => 'kepsek_sd',
                'role' => 'kepala_sekolah',
            ],
            [
                'name' => 'Staf Tata Usaha SD',
                'email' => 'tu_sd@baitulmakmur.sch.id',
                'username' => 'tu_sd',
                'role' => 'tata_usaha',
            ],
            [
                'name' => 'Guru Pengajar SD',
                'email' => 'guru_sd@baitulmakmur.sch.id',
                'username' => 'guru_sd',
                'role' => 'guru',
            ],
        ];

        foreach ($defaultUsers as $uData) {
            $user = User::withoutGlobalScopes()->where('email', $uData['email'])->first();
            if (!$user) {
                $user = User::withoutGlobalScopes()->create([
                    'name' => $uData['name'],
                    'email' => $uData['email'],
                    'username' => $uData['username'],
                    'password' => Hash::make('password'),
                    'institution_id' => $sdId,
                    'is_active' => true,
                    'has_account' => true,
                ]);
            } else {
                $user->update([
                    'institution_id' => $sdId,
                    'is_active' => true,
                ]);
            }

            if (!$user->hasRole($uData['role'])) {
                $user->assignRole($uData['role']);
            }

            // Sync pivot institution_user
            if (Schema::hasTable('institution_user')) {
                DB::table('institution_user')->updateOrInsert(
                    ['user_id' => $user->id, 'institution_id' => $sdId],
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
        // No-op rollback agar aman
    }
};
