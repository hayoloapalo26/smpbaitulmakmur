<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\MandatoryTask;
use App\Models\MandatoryTaskSubmission;
use App\Models\OptionalTask;
use App\Models\OptionalTaskSubmission;
use App\Models\CharacterScoreLog;
use App\Models\DailyAttendance;
use App\Models\TeacherAttendance;
use App\Models\SchoolWorkPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SmpProgressSeeder extends Seeder
{
    public function run(): void
    {
        $smpId = 2; // ID Institusi SMP

        // 1. Setup Data Approval RKT/RKS
        SchoolWorkPlan::create([
            'institution_id' => $smpId,
            'document_type' => 'RKT',
            'drive_link' => 'https://docs.google.com/document/d/draft-rkt',
            'status' => 'draft',
            'created_at' => Carbon::now()->subDays(5)
        ]);

        SchoolWorkPlan::create([
            'institution_id' => $smpId,
            'document_type' => 'RKS',
            'drive_link' => 'https://docs.google.com/document/d/revision-rks',
            'status' => 'revision_ketua',
            'ketua_note' => 'Mohon tambahkan rincian anggaran untuk kegiatan ekstrakurikuler.',
            'created_at' => Carbon::now()->subDays(10),
            'updated_at' => Carbon::now()->subDays(2)
        ]);

        SchoolWorkPlan::create([
            'institution_id' => $smpId,
            'document_type' => 'RKT',
            'drive_link' => 'https://docs.google.com/document/d/approved-rkt',
            'status' => 'approved_final',
            'ketua_note' => 'Sudah cukup komprehensif. Lanjut ke Pembina.',
            'pembina_note' => 'ACC.',
            'created_at' => Carbon::now()->subDays(20),
            'updated_at' => Carbon::now()->subDays(1)
        ]);

        // 2. Setup Data KPI Guru (Mandatory & Optional Tasks)
        $task = MandatoryTask::create([
            'institution_id' => $smpId,
            'title' => 'Laporan Jurnal Mengajar Harian',
            'description' => 'Harus diisi setiap hari setelah KBM.',
            'target_roles' => ['guru_mapel', 'guru_kelas', 'guru_piket'],
            'form_schema' => [],
            'is_active' => true,
        ]);

        $optTask = OptionalTask::create([
            'institution_id' => $smpId,
            'title' => 'Pembinaan Ekstrakurikuler Tambahan',
            'description' => 'Membimbing siswa di luar jam KBM.',
            'target_roles' => ['guru_mapel', 'guru_kelas'],
            'form_schema' => [],
            'is_active' => true,
            'reward_points' => 5,
        ]);

        $teachers = User::where('institution_id', $smpId)
                        ->whereHas('roles', function($q){
                            $q->whereIn('name', ['guru_mapel', 'guru_kelas', 'guru_piket']);
                        })->get();

        // 3. Daily Attendance untuk Guru dan Siswa & KPI Submission
        $students = Student::all(); // All students (we created 5 for SMP)

        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $isWeekend = Carbon::parse($date)->isWeekend();
            
            if ($isWeekend) continue; // Skip weekend

            // A. Guru Attendances & Submissions
            foreach ($teachers as $idx => $teacher) {
                // Vary completion for ranking
                // $idx 0 -> 90% attendance, 1 -> 50%, 2 -> 100%
                $attendChance = 100;
                if ($idx == 1) $attendChance = 50;
                if ($idx == 0) $attendChance = 90;
                
                $isPresent = rand(1, 100) <= $attendChance;

                if ($isPresent) {
                    TeacherAttendance::create([
                        'teacher_id' => $teacher->id,
                        'tanggal' => $date,
                        'status' => 'hadir'
                    ]);

                    // Submit Mandatory task if present
                    if (rand(1, 100) <= $attendChance) {
                        MandatoryTaskSubmission::create([
                            'mandatory_task_id' => $task->id,
                            'user_id' => $teacher->id,
                            'answers' => [],
                            'submitted_at' => Carbon::parse($date)->setHour(14)
                        ]);
                    }

                    // Submit Optional task occasionally
                    if ($teacher->hasRole('guru_kelas') && rand(1, 100) <= ($attendChance / 2)) {
                        OptionalTaskSubmission::create([
                            'optional_task_id' => $optTask->id,
                            'user_id' => $teacher->id,
                            'answers' => [],
                            'submitted_at' => Carbon::parse($date)->setHour(15)
                        ]);
                    }
                } else {
                    TeacherAttendance::create([
                        'teacher_id' => $teacher->id,
                        'tanggal' => $date,
                        'status' => rand(1, 100) > 50 ? 'izin' : 'sakit'
                    ]);
                }
            }

            // B. Siswa Attendances & Character Logs
            $teacherPiket = $teachers->first(); // Just use the first teacher as inputter
            
            foreach ($students as $student) {
                // Attendance
                $status = rand(1, 100) > 90 ? (rand(1, 100) > 50 ? 'sakit' : 'izin') : 'hadir';
                $isLate = ($status == 'hadir') && (rand(1, 100) > 85);
                
                DailyAttendance::create([
                    'student_id' => $student->id,
                    'teacher_id' => $teacherPiket->id,
                    'tanggal' => $date,
                    'status' => $status,
                    'is_late' => $isLate,
                    'waktu_kedatangan' => $isLate ? '07:30:00' : '06:45:00'
                ]);

                // Character Score Logs (Guru Kelas, Piket, Orang Tua)
                if ($status == 'hadir' && rand(1, 100) > 70) {
                    CharacterScoreLog::create([
                        'student_id' => $student->id,
                        'source' => 'guru_kelas',
                        'category' => 'akademik',
                        'description' => 'Aktif bertanya di kelas',
                        'point_change' => 2,
                        'tanggal' => $date
                    ]);
                }

                if ($isLate) {
                    CharacterScoreLog::create([
                        'student_id' => $student->id,
                        'source' => 'guru_piket',
                        'category' => 'kedisiplinan',
                        'description' => 'Terlambat masuk sekolah',
                        'point_change' => -2,
                        'tanggal' => $date
                    ]);
                }

                if (rand(1, 100) > 30) {
                    CharacterScoreLog::create([
                        'student_id' => $student->id,
                        'source' => 'orang_tua',
                        'category' => 'kepribadian',
                        'description' => 'Mengerjakan amaliyah malam',
                        'point_change' => 1,
                        'tanggal' => $date
                    ]);
                }
            }
        }
    }
}
