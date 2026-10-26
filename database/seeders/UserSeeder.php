<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\StudentGuardian;
use App\Models\DutySchedule;
use App\Models\TeacherSubjectClass;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================
        // 1. MASTER DATA: TAHUN AJARAN & MATA PELAJARAN
        // ==========================
        AcademicYear::create(['name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
        
        $subjectsData = [
            'PAI' => Subject::create(['name' => 'Pendidikan Agama Islam', 'kode' => 'PAI']),
            'MTK' => Subject::create(['name' => 'Matematika', 'kode' => 'MTK']),
            'BIN' => Subject::create(['name' => 'Bahasa Indonesia', 'kode' => 'BIN']),
            'IPA' => Subject::create(['name' => 'Ilmu Pengetahuan Alam', 'kode' => 'IPA']),
            'BING' => Subject::create(['name' => 'Bahasa Inggris', 'kode' => 'BING']),
            'IPS' => Subject::create(['name' => 'Ilmu Pengetahuan Sosial', 'kode' => 'IPS']),
            'PKN' => Subject::create(['name' => 'Pendidikan Kewarganegaraan', 'kode' => 'PKN']),
            'SBD' => Subject::create(['name' => 'Seni Budaya', 'kode' => 'SBD']),
            'PJOK' => Subject::create(['name' => 'Pendidikan Jasmani & Olahraga', 'kode' => 'PJOK']),
        ];

        // ==========================
        // 2. DATA PEGAWAI / GURU / STAF
        // ==========================
        $staffData = [
            // Manajemen
            ['email' => 'admin@gmail.com', 'name' => 'Budi Santoso, S.Kom', 'role' => 'superadmin', 'jabatan' => 'Admin Sistem', 'gender' => 'L', 'status_kepegawaian' => 'Karyawan'],
            ['email' => 'kepsek@gmail.com', 'name' => 'Drs. H. Ahmad Dahlan, M.Pd', 'role' => 'kepala_sekolah', 'jabatan' => 'Kepala Sekolah', 'gender' => 'L', 'status_kepegawaian' => 'GTY'],
            ['email' => 'wakakur@gmail.com', 'name' => 'Siti Aminah, M.Pd', 'role' => 'waka_kurikulum', 'jabatan' => 'Waka Kurikulum', 'gender' => 'P', 'status_kepegawaian' => 'GTY'],
            ['email' => 'wakakes@gmail.com', 'name' => 'Abdul Rahman, S.Pd', 'role' => 'waka_kesiswaan', 'jabatan' => 'Waka Kesiswaan', 'gender' => 'L', 'status_kepegawaian' => 'GTY'],
            ['email' => 'tu@gmail.com', 'name' => 'Dewi Sartika, S.E', 'role' => 'tata_usaha', 'jabatan' => 'Kepala Tata Usaha', 'gender' => 'P', 'status_kepegawaian' => 'GTY'],
            
            // 7 Guru Mapel Utama
            ['email' => 'guru.mtk@gmail.com', 'name' => 'Rina Melati, S.Pd', 'role' => 'guru_mapel', 'jabatan' => 'Guru Matematika', 'gender' => 'P', 'status_kepegawaian' => 'GTY', 'mapel' => 'MTK'],
            ['email' => 'guru.ipa@gmail.com', 'name' => 'Eko Prasetyo, S.Pd', 'role' => 'guru_mapel', 'jabatan' => 'Guru IPA', 'gender' => 'L', 'status_kepegawaian' => 'GTT', 'mapel' => 'IPA'],
            ['email' => 'guru.bin@gmail.com', 'name' => 'Yusuf Mansur, S.Pd', 'role' => 'guru_mapel', 'jabatan' => 'Guru Bhs Indonesia', 'gender' => 'L', 'status_kepegawaian' => 'GTY', 'mapel' => 'BIN'],
            ['email' => 'guru.bing@gmail.com', 'name' => 'Sarah Wijayanto, S.Pd', 'role' => 'guru_mapel', 'jabatan' => 'Guru Bhs Inggris', 'gender' => 'P', 'status_kepegawaian' => 'GTY', 'mapel' => 'BING'],
            ['email' => 'guru.ips@gmail.com', 'name' => 'Bambang Pamungkas, S.Pd', 'role' => 'guru_mapel', 'jabatan' => 'Guru IPS', 'gender' => 'L', 'status_kepegawaian' => 'GTT', 'mapel' => 'IPS'],
            ['email' => 'guru.pkn@gmail.com', 'name' => 'Andi Sudirman, S.Pd', 'role' => 'guru_mapel', 'jabatan' => 'Guru PKN', 'gender' => 'L', 'status_kepegawaian' => 'GTY', 'mapel' => 'PKN'],
            ['email' => 'guru.pjok@gmail.com', 'name' => 'Rio Haryanto, S.Pd', 'role' => 'guru_mapel', 'jabatan' => 'Guru PJOK', 'gender' => 'L', 'status_kepegawaian' => 'GTT', 'mapel' => 'PJOK'],
            
            // Guru Khusus
            ['email' => 'alquran@gmail.com', 'name' => 'Ust. Lukman Hakim, S.Th.I', 'role' => 'guru_alquran', 'jabatan' => 'Koordinator Tahfidz', 'gender' => 'L', 'status_kepegawaian' => 'GTY'],
            ['email' => 'wali7a@gmail.com', 'name' => 'Fitri Anggraini, S.Pd', 'role' => 'wali_kelas', 'jabatan' => 'Wali Kelas 7A', 'gender' => 'P', 'status_kepegawaian' => 'GTY', 'mapel' => 'SBD'],
            ['email' => 'wali8a@gmail.com', 'name' => 'Herman Felani, S.Pd', 'role' => 'wali_kelas', 'jabatan' => 'Wali Kelas 8A', 'gender' => 'L', 'status_kepegawaian' => 'GTY', 'mapel' => 'PAI'],
            ['email' => 'wali9a@gmail.com', 'name' => 'Dian Sastrowardoyo, S.Pd', 'role' => 'wali_kelas', 'jabatan' => 'Wali Kelas 9A', 'gender' => 'P', 'status_kepegawaian' => 'GTY', 'mapel' => 'PAI'],
            ['email' => 'bk@gmail.com', 'name' => 'Linda Kumalasari, S.Psi', 'role' => 'bk', 'jabatan' => 'Guru Bimbingan Konseling', 'gender' => 'P', 'status_kepegawaian' => 'GTY'],
            
            // Guru Ekstrakurikuler
            ['email' => 'ekskul@gmail.com', 'name' => 'Agus Pramono, S.Sn', 'role' => 'guru_ekstrakurikuler', 'jabatan' => 'Pembina Ekstrakurikuler', 'gender' => 'L', 'status_kepegawaian' => 'GTT'],
        ];

        $createdUsers = [];
        foreach ($staffData as $i => $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'no_induk_yayasan' => 'YBM-' . (1001 + $i),
                'nuptk' => '123456789' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'gender' => $data['gender'],
                'jabatan' => $data['jabatan'],
                'status_kepegawaian' => $data['status_kepegawaian'],
                'no_hp' => '0812' . rand(1000000, 9999999),
                'tempat_lahir' => 'Malang',
                'tanggal_lahir' => Carbon::now()->subYears(rand(25, 50))->format('Y-m-d'),
                'jenjang_pendidikan' => 'S1',
                'universitas' => 'Universitas Negeri Malang',
                'tanggal_masuk' => Carbon::now()->subYears(rand(1, 10))->format('Y-m-d'),
                'alamat' => 'Perumahan Guru No. ' . ($i + 1),
            ]);
            
            if (Role::where('name', $data['role'])->count() == 0) {
                Role::create(['name' => $data['role']]);
            }
            $user->assignRole($data['role']);
            $createdUsers[$data['email']] = $user;
        }

        // ==========================
        // 3. MASTER DATA: KELAS
        // ==========================
        $classrooms = [
            Classroom::create(['name' => '7A', 'tingkat' => '7', 'wali_kelas_id' => $createdUsers['wali7a@gmail.com']->id]),
            Classroom::create(['name' => '8A', 'tingkat' => '8', 'wali_kelas_id' => $createdUsers['wali8a@gmail.com']->id]),
            Classroom::create(['name' => '9A', 'tingkat' => '9', 'wali_kelas_id' => $createdUsers['wali9a@gmail.com']->id]),
        ];

        // ==========================
        // 3b. MAPPING MATA PELAJARAN GURU (TeacherSubjectClass)
        // ==========================
        foreach ($staffData as $data) {
            if (isset($data['mapel'])) {
                $teacherId = $createdUsers[$data['email']]->id;
                $subjectId = $subjectsData[$data['mapel']]->id;
                // Tugaskan ke semua kelas
                foreach ($classrooms as $cls) {
                    TeacherSubjectClass::create([
                        'teacher_id' => $teacherId,
                        'subject_id' => $subjectId,
                        'classroom_id' => $cls->id
                    ]);
                }
            }
        }

        // ==========================
        // 4. DATA SISWA & ORANG TUA
        // ==========================
        $studentNames = [
            ['name' => 'Ahsan Maulana', 'gender' => 'L'], ['name' => 'Bima Sakti', 'gender' => 'L'],
            ['name' => 'Citra Lestari', 'gender' => 'P'], ['name' => 'Dewi Safitri', 'gender' => 'P'],
            ['name' => 'Fajar Nugroho', 'gender' => 'L'], ['name' => 'Gilang Ramadhan', 'gender' => 'L'],
            ['name' => 'Hana Pertiwi', 'gender' => 'P'], ['name' => 'Intan Permatasari', 'gender' => 'P'],
            ['name' => 'Kevin Sanjaya', 'gender' => 'L'], ['name' => 'Lestari Indah', 'gender' => 'P'],
        ];

        foreach ($studentNames as $i => $std) {
            $class = $classrooms[$i % 3];
            $student = Student::create([
                'nis' => '100' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'name' => $std['name'],
                'classroom_id' => $class->id,
                'tanggal_lahir' => Carbon::now()->subYears(13)->format('Y-m-d'),
                'tempat_lahir' => 'Malang',
                'jenis_kelamin' => $std['gender'],
                'alamat_siswa' => 'Jl. Mawar Merah No. ' . ($i + 1) . ', Malang',
                'no_telepon_siswa' => '081300000' . $i,
                'asal_sekolah' => 'SDN ' . ($i % 5 + 1) . ' Malang',
                'nama_ayah' => 'Bapak ' . $std['name'],
                'pekerjaan_ayah' => $i % 2 == 0 ? 'PNS' : 'Wiraswasta',
                'nama_ibu' => 'Ibu ' . $std['name'],
                'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                'no_telepon_orang_tua' => '085200000' . $i,
                'character_score' => 80
            ]);

            $studentUser = User::create([
                'name' => $std['name'],
                'email' => 'siswa' . ($i + 1) . '@gmail.com',
                'password' => Hash::make('password'),
                'student_id' => $student->id
            ]);
            $studentUser->assignRole('siswa');

            $guardian = Guardian::create([
                'name' => 'Keluarga ' . $std['name'],
                'phone' => '085200000' . $i,
                'address' => 'Jl. Mawar Merah No. ' . ($i + 1) . ', Malang'
            ]);
            
            StudentGuardian::create([
                'student_id' => $student->id, 'guardian_id' => $guardian->id, 'relation' => 'ayah'
            ]);

            if ($i < 3) {
                $ortuUser = User::create([
                    'name' => 'Wali dari ' . $std['name'],
                    'email' => 'ortu' . ($i + 1) . '@gmail.com',
                    'password' => Hash::make('password'),
                    'guardian_id' => $guardian->id
                ]);
                $ortuUser->assignRole('orang_tua');
            }
        }

        // ==========================
        // 5. DATA JADWAL PIKET (ROUTING OLEH WAKA KESISWAAN)
        // ==========================
        $today = Carbon::today();
        $tomorrow = Carbon::today()->addDay();
        $nextDay = Carbon::today()->addDays(2);
        
        // Waka Kesiswaan merouting dirinya dan guru MTK piket hari ini
        DutySchedule::create(['teacher_id' => $createdUsers['wakakes@gmail.com']->id, 'tanggal' => $today]);
        DutySchedule::create(['teacher_id' => $createdUsers['guru.mtk@gmail.com']->id, 'tanggal' => $today]);
        
        // Piket Besok: Guru IPA dan Guru BIN
        DutySchedule::create(['teacher_id' => $createdUsers['guru.ipa@gmail.com']->id, 'tanggal' => $tomorrow]);
        DutySchedule::create(['teacher_id' => $createdUsers['guru.bin@gmail.com']->id, 'tanggal' => $tomorrow]);
        
        // Piket Lusa: Guru BING dan Guru IPS
        DutySchedule::create(['teacher_id' => $createdUsers['guru.bing@gmail.com']->id, 'tanggal' => $nextDay]);
        DutySchedule::create(['teacher_id' => $createdUsers['guru.ips@gmail.com']->id, 'tanggal' => $nextDay]);
    }
}
