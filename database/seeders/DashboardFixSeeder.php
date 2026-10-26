<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicYear;
use App\Models\Institution;
use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\TeacherAttendance;
use App\Models\MandatoryTask;
use App\Models\MandatoryTaskSubmission;
use App\Models\SchoolPillarTask;
use App\Models\SchoolPillar;
use Carbon\Carbon;

class DashboardFixSeeder extends Seeder
{
    public function run(): void
    {
        $smpId = 2;
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // 1. Academic Year Aktif
        AcademicYear::updateOrCreate(
            ['is_active' => true],
            [
                'name' => '2025/2026', 
                'start_date' => Carbon::now()->startOfYear(), 
                'end_date' => Carbon::now()->addYear()->endOfYear()
            ]
        );

        // 2. Setup Kelas untuk SMP Islam (Institution ID = 2)
        // Ensure Classroom has institution_id if it exists, but we will just pass it if applicable
        $classroom = Classroom::firstOrCreate([
            'name' => 'Kelas 7A',
            'grade_level' => '7'
        ]);
        
        // If the schema supports it, we assign it directly (or via DB if there is no fillable property setup)
        DB::table('classrooms')->where('id', $classroom->id)->update(['institution_id' => $smpId]);

        // 3. Fix "Total Siswa Aktif" (Minimal 50 Data)
        for ($i = 1; $i <= 50; $i++) {
            Student::updateOrCreate(
                ['nis' => 'SMP2026' . str_pad($i, 3, '0', STR_PAD_LEFT)],
                [
                    'name' => 'Siswa Aktif ' . $i,
                    'classroom_id' => $classroom->id,
                    'tanggal_lahir' => Carbon::now()->subYears(13)->subDays(rand(1, 300)),
                    'jenis_kelamin' => $i % 2 == 0 ? 'L' : 'P',
                    'character_score' => rand(70, 100),
                    // Jika ada status/is_active, asumsikan aktif karena tidak ada di default migration
                ]
            );
        }

        // 4. Fix "Total Guru" (Minimal 10 Data)
        $teachers = [];
        for ($i = 1; $i <= 10; $i++) {
            $teacher = User::updateOrCreate(
                ['email' => "guru_smp{$i}@smp.com"],
                [
                    'name' => 'Guru Pengajar ' . $i,
                    'username' => "guru_smp{$i}",
                    'password' => bcrypt('password123'),
                    'institution_id' => $smpId,
                    'is_active' => true,
                ]
            );
            
            // Assign roles
            if (!$teacher->hasRole('guru_mapel')) $teacher->assignRole('guru_mapel');
            if ($i <= 3 && !$teacher->hasRole('wali_kelas')) $teacher->assignRole('wali_kelas');
            
            $teachers[] = $teacher;
        }

        // 5. Fix "Peringkat Kinerja Guru" & Rata-Rata KPI (Attendances & Tasks HARI INI)
        $task = MandatoryTask::firstOrCreate([
            'title' => 'Laporan Jurnal Mengajar Harian (Hari Ini)',
            'institution_id' => $smpId,
        ], [
            'description' => 'Tugas ini wajib dikerjakan hari ini.',
            'target_roles' => ['guru_mapel', 'wali_kelas'],
            'form_schema' => [],
            'is_active' => true,
        ]);

        foreach ($teachers as $idx => $teacher) {
            // A. Kehadiran HARI INI
            TeacherAttendance::updateOrCreate([
                'teacher_id' => $teacher->id,
                'tanggal' => $today,
            ], [
                'status' => 'hadir'
            ]);

            // B. Submission Tugas HARI INI (bervariasi)
            // 7 dari 10 guru mengerjakan, sisanya tidak (untuk meragamkan persentase KPI)
            if ($idx < 7) {
                MandatoryTaskSubmission::updateOrCreate([
                    'mandatory_task_id' => $task->id,
                    'user_id' => $teacher->id,
                ], [
                    'answers' => [],
                    'submitted_at' => $now
                ]);
            }
        }

        // 6. Fix "Ketercapaian Global" (0 / 12 Tugas Tuntas)
        // Set up to 7 existing tasks to 'selesai' to achieve ~58% completion.
        $tasks = SchoolPillarTask::take(12)->get();
        if ($tasks->count() < 12) {
            // Jika ternyata kurang dari 12, kita tambahkan dummy agar genap 12
            $pillar = SchoolPillar::firstOrCreate(['name' => 'Bidang Manajemen SMP']);
            DB::table('school_pillars')->where('id', $pillar->id)->update(['institution_id' => $smpId]);
            
            $needed = 12 - $tasks->count();
            for ($i = 1; $i <= $needed; $i++) {
                SchoolPillarTask::create([
                    'school_pillar_id' => $pillar->id,
                    'name' => 'Tugas Indikator Tambahan ' . $i,
                    'code' => '1.X.' . $i,
                    'assigned_role' => 'KS, WKr, TU',
                    'status' => 'belum',
                ]);
            }
            $tasks = SchoolPillarTask::take(12)->get();
        }

        // Set 7 tasks to selesai
        foreach ($tasks->values() as $idx => $t) {
            if ($idx < 7) {
                $t->update([
                    'status' => 'selesai',
                    'completed_at' => $now,
                    'completed_by' => 1 
                ]);
            } else {
                $t->update([
                    'status' => 'belum',
                    'completed_at' => null,
                ]);
            }
        }
    }
}
