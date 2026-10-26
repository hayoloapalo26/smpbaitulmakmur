<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\DailyAttendance;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\CharacterScoreLog;
use App\Models\ParentDailyChecklist;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class ParentDashboardSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan Role dan AcademicYear
        Role::firstOrCreate(['name' => 'orang_tua', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'siswa', 'guard_name' => 'web']);
        
        $academicYear = AcademicYear::firstOrCreate(
            ['is_active' => true],
            ['name' => '2025/2026', 'start_date' => Carbon::now()->startOfYear(), 'end_date' => Carbon::now()->addYear()->endOfYear()]
        );

        // 2. Buat Data Anak & Orang Tua Ideal (Bapak Budi & Anaknya Budi Junior)
        $classroom = Classroom::firstOrCreate(['name' => 'Kelas 8A', 'tingkat' => '8']);
        if (empty($classroom->institution_id)) {
            DB::table('classrooms')->where('id', $classroom->id)->update(['institution_id' => 2]);
        }

        $student = Student::updateOrCreate(
            ['nis' => 'SMP-100999'],
            [
                'name' => 'Ahmad Budi Junior',
                'classroom_id' => $classroom->id,
                'tanggal_lahir' => Carbon::now()->subYears(14)->format('Y-m-d'),
                'jenis_kelamin' => 'L',
                'character_score' => 85,
                'is_administrasi_lunas' => true,
            ]
        );

        $guardian = \App\Models\Guardian::firstOrCreate(
            ['name' => 'Bpk. Budi Santoso'],
            ['phone' => '081234567890', 'address' => 'Jl. Merdeka No 1']
        );

        DB::table('student_guardians')->updateOrInsert(
            ['student_id' => $student->id, 'guardian_id' => $guardian->id],
            ['relation' => 'Ayah', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        );

        $parent = User::updateOrCreate(
            ['email' => 'ortu.budi@smpi.com'],
            [
                'name' => 'Bpk. Budi Santoso (Ortu Ahmad)',
                'username' => 'ortubudi',
                'password' => Hash::make('password123'),
                'guardian_id' => $guardian->id,
                'student_id' => null, // Bersihkan jika ada sisa
                'institution_id' => 2,
                'is_active' => true,
            ]
        );
        $parent->syncRoles(['orang_tua']);

        // 3. Data Akademik (Kehadiran & Nilai)
        $teacher = User::first() ?? User::factory()->create(); // Ambil guru sembarang utk id
        $today = Carbon::today();
        
        // 30 hari kehadiran harian (90% hadir, sisanya izin/sakit)
        for ($i = 0; $i < 30; $i++) {
            $date = $today->copy()->subDays($i);
            if ($date->isWeekend()) continue;

            $status = 'hadir';
            if ($i == 5) $status = 'izin';
            if ($i == 15) $status = 'sakit';

            DailyAttendance::updateOrCreate(
                ['student_id' => $student->id, 'tanggal' => $date->format('Y-m-d')],
                ['teacher_id' => $teacher->id, 'status' => $status, 'waktu_kedatangan' => '06:45:00', 'is_late' => false]
            );
        }

        // Mata Pelajaran & Nilai
        $matematika = Subject::firstOrCreate(['name' => 'Matematika'], ['kode' => 'MAT']);
        $ipa = Subject::firstOrCreate(['name' => 'Ilmu Pengetahuan Alam'], ['kode' => 'IPA']);
        
        Grade::updateOrCreate(['student_id' => $student->id, 'subject_id' => $matematika->id, 'type' => 'ujian'], ['score' => 90, 'semester' => 'Ganjil', 'academic_year_id' => $academicYear->id]);
        Grade::updateOrCreate(['student_id' => $student->id, 'subject_id' => $matematika->id, 'type' => 'pengetahuan'], ['score' => 85, 'semester' => 'Ganjil', 'academic_year_id' => $academicYear->id]);
        Grade::updateOrCreate(['student_id' => $student->id, 'subject_id' => $ipa->id, 'type' => 'ujian'], ['score' => 88, 'semester' => 'Ganjil', 'academic_year_id' => $academicYear->id]);
        Grade::updateOrCreate(['student_id' => $student->id, 'subject_id' => $ipa->id, 'type' => 'keterampilan'], ['score' => 92, 'semester' => 'Ganjil', 'academic_year_id' => $academicYear->id]);

        // 4. Data Non-Akademik (Ekstrakurikuler)
        $pramuka = Extracurricular::firstOrCreate(['name' => 'Pramuka'], ['pembina_id' => $teacher->id]);
        $futsal = Extracurricular::firstOrCreate(['name' => 'Futsal'], ['pembina_id' => $teacher->id]);
        
        // Kehadiran ekskul
        for ($i = 0; $i < 4; $i++) {
            $date = $today->copy()->subWeeks($i);
            ExtracurricularAttendance::updateOrCreate(
                ['extracurricular_id' => $pramuka->id, 'student_id' => $student->id, 'tanggal' => $date->format('Y-m-d')],
                ['status' => 'hadir']
            );
            // Sesekali absen futsal
            ExtracurricularAttendance::updateOrCreate(
                ['extracurricular_id' => $futsal->id, 'student_id' => $student->id, 'tanggal' => $date->format('Y-m-d')],
                ['status' => $i == 2 ? 'izin' : 'hadir']
            );
        }

        // 5. Data Karakter di Sekolah
        CharacterScoreLog::create(['student_id' => $student->id, 'source' => 'guru_kelas', 'category' => 'Kedisiplinan', 'description' => 'Membantu membersihkan kelas.', 'point_change' => 5, 'tanggal' => $today->copy()->subDays(2)]);
        CharacterScoreLog::create(['student_id' => $student->id, 'source' => 'guru_piket', 'category' => 'Ibadah', 'description' => 'Rajin sholat dhuha.', 'point_change' => 10, 'tanggal' => $today->copy()->subDays(5)]);
        CharacterScoreLog::create(['student_id' => $student->id, 'source' => 'guru_kelas', 'category' => 'Sikap', 'description' => 'Terlambat masuk setelah istirahat.', 'point_change' => -2, 'tanggal' => $today->copy()->subDays(10)]);

        // 6. Data Amaliyah Orang Tua di Rumah (Checklist)
        $activities = ['Sholat Subuh Berjamaah', 'Membaca Al-Quran 1 Lembar', 'Membantu Orang Tua', 'Belajar Malam'];
        for ($i = 0; $i < 7; $i++) {
            $date = $today->copy()->subDays($i);
            foreach ($activities as $idx => $act) {
                ParentDailyChecklist::updateOrCreate(
                    ['student_id' => $student->id, 'tanggal' => $date->format('Y-m-d'), 'activity' => $act],
                    ['time_slot' => $idx % 2 == 0 ? 'pagi' : 'malam', 'is_done' => rand(0, 100) > 20] // 80% selesai
                );
            }
        }
    }
}
