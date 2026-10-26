<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Institution;
use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class InstitutionUserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Institutions
        $yayasan = Institution::updateOrCreate(
            ['id' => 1],
            ['name' => 'Yayasan Baitul Makmur', 'slug' => 'yayasan']
        );

        $smp = Institution::updateOrCreate(['id' => 2], ['name' => 'SMP Islam Baitul Makmur', 'slug' => 'smp']);
        Institution::updateOrCreate(['id' => 3], ['name' => 'KB/TK Islam Baitul Makmur', 'slug' => 'tk']);
        Institution::updateOrCreate(['id' => 4], ['name' => 'SD Islam Baitul Makmur', 'slug' => 'sd']);
        Institution::updateOrCreate(['id' => 5], ['name' => 'SMK Baitul Makmur', 'slug' => 'smk']);
        Institution::updateOrCreate(['id' => 6], ['name' => 'MA Baitul Makmur', 'slug' => 'ma']);
        Institution::updateOrCreate(['id' => 7], ['name' => 'Pondok Pesantren Baitul Makmur', 'slug' => 'ponpes']);


        // 2. Create Roles
        $roles = [
            'superadmin', 'ketua_yayasan', 'pembina_yayasan', 'sekretaris_yayasan', 'tim_pengembang_penjamin_mutu_yayasan',
            'pegawai_keuangan_yayasan', 'pegawai_keuangan',
            'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'guru_mapel', 'guru_kelas', 'wali_kelas', 'guru_alquran', 'bk', 'tata_usaha', 'sarpras', 'humas', 'guru_piket', 
            'siswa', 'orang_tua'
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        $defaultPassword = Hash::make('password123');

        // 3. Yayasan Users
        $this->createUser('Super Admin', 'superadmin@yayasan.com', 'superadmin', 1, $defaultPassword);
        $this->createUser('Ketua Yayasan', 'ketua@yayasan.com', 'ketua_yayasan', 1, $defaultPassword);
        $this->createUser('Pembina Yayasan', 'pembina@yayasan.com', 'pembina_yayasan', 1, $defaultPassword);
        $this->createUser('Sekretaris Yayasan', 'sekretaris@yayasan.com', 'sekretaris_yayasan', 1, $defaultPassword);
        $this->createUser('Pegawai Keuangan Yayasan', 'keuangan@yayasan.com', 'pegawai_keuangan_yayasan', 1, $defaultPassword);

        // 4. SMP Users (Staff)
        $kepsek = $this->createUser('Kepala Sekolah SMP', 'kepsek@smp.com', 'kepala_sekolah', 2, $defaultPassword);
        $waka = $this->createUser('Waka Kurikulum SMP', 'waka@smp.com', 'waka_kurikulum', 2, $defaultPassword);
        $guru1 = $this->createUser('Guru Budi (Kelas)', 'gurubudi@smp.com', 'guru_kelas', 2, $defaultPassword);
        $guru1->assignRole(['wali_kelas', 'guru_alquran', 'bk', 'tata_usaha', 'sarpras', 'humas']);
        $guru2 = $this->createUser('Guru Siti (Piket)', 'gurusiti@smp.com', 'guru_piket', 2, $defaultPassword);
        $guru3 = $this->createUser('Guru Anton (Mapel)', 'guruanton@smp.com', 'guru_mapel', 2, $defaultPassword);

        // Create a classroom first
        $classroom = Classroom::firstOrCreate(['name' => 'Kelas 7A', 'tingkat' => '7']);

        // 5. Students & Parents
        for ($i = 1; $i <= 5; $i++) {
            $student = Student::firstOrCreate(
                ['nis' => '100'.$i],
                [
                    'name' => 'Siswa ' . $i, 
                    'jenis_kelamin' => $i % 2 == 0 ? 'L' : 'P',
                    'tanggal_lahir' => '2010-01-01',
                    'character_score' => rand(60, 100),
                    'classroom_id' => $classroom->id
                ]
            );

            // Student User
            $studentUser = $this->createUser('Siswa ' . $i, "siswa$i@smp.com", 'siswa', 2, $defaultPassword);
            $studentUser->update(['student_id' => $student->id]);

            // Parent User
            $parentUser = $this->createUser('Ortu Siswa ' . $i, "ortu$i@smp.com", 'orang_tua', 2, $defaultPassword);
            $parentUser->update(['student_id' => $student->id]);
        }
    }

    private function createUser($name, $email, $role, $institutionId, $password)
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => explode('@', $email)[0],
                'password' => $password,
                'institution_id' => $institutionId,
                'is_active' => true,
            ]
        );

        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }
}



