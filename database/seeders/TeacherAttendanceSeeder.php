<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TeacherAttendance;
use Carbon\Carbon;

class TeacherAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = User::role(['guru_mapel', 'guru_alquran', 'wali_kelas', 'waka_kurikulum', 'waka_kesiswaan', 'kepala_sekolah'])->get();
        $statuses = ['hadir', 'hadir', 'hadir', 'hadir', 'izin', 'sakit', 'alpa']; 

        foreach ($teachers as $teacher) {
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::today()->subDays($i)->format('Y-m-d');
                
                TeacherAttendance::updateOrCreate(
                    ['teacher_id' => $teacher->id, 'tanggal' => $date],
                    ['status' => $statuses[array_rand($statuses)]]
                );
            }
        }
        
        echo "Dummy presensi guru (Teacher Attendance) 30 hari terakhir berhasil dibuat untuk " . $teachers->count() . " guru!\n";
    }
}
