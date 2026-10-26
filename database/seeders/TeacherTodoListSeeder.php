<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TeacherTodoList;
use Carbon\Carbon;

class TeacherTodoListSeeder extends Seeder
{
    public function run(): void
    {
        // Get some teachers
        $teachers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'waka_kurikulum', 'waka_kesiswaan', 'bk']);
        })->get();

        if ($teachers->isEmpty()) return;

        TeacherTodoList::query()->delete();

        $tasks = [
            'Menyusun RPP Bab 3',
            'Mengoreksi ulangan harian',
            'Rekap presensi siswa',
            'Melakukan bimbingan dengan siswa perwalian',
            'Update Jurnal Mengajar di sistem',
            'Koordinasi dengan guru BK',
            'Menyiapkan materi presentasi',
            'Membuat soal PTS',
            'Rapat evaluasi mingguan',
            'Konsultasi dengan kepala sekolah',
        ];

        $today = Carbon::today();
        
        foreach ($teachers as $index => $teacher) {
            // Rajin teachers get more tasks done
            $isRajin = $index % 3 == 0;
            $taskCount = $isRajin ? rand(8, 12) : rand(3, 6);
            
            for ($i = 0; $i < $taskCount; $i++) {
                $isDone = $isRajin ? (rand(1, 100) > 20) : (rand(1, 100) > 50); // Rajin = 80% done, Others = 50% done
                $daysSub = rand(0, 5);
                
                TeacherTodoList::create([
                    'teacher_id' => $teacher->id,
                    'tanggal' => $today->copy()->subDays($daysSub),
                    'tugas' => $tasks[array_rand($tasks)],
                    'is_done' => $isDone,
                    'completed_at' => $isDone ? $today->copy()->subDays($daysSub)->addHours(rand(8, 15)) : null,
                ]);
            }
        }
    }
}
