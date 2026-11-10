<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\TeacherSubjectClass;
use App\Models\Schedule;
use App\Console\Commands\RestoreCurriculumScheduleCommand;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Memastikan tabel Setting Guru Mapel (teacher_subject_classes) dan Jadwal Pelajaran (schedules)
     * kembali terisi penuh dan terhubung ke seluruh guru pengampu resmi SMP.
     */
    public function up(): void
    {
        // 1. Pastikan record institusi SMP (id=2) tersedia
        if (DB::table('institutions')->count() > 0) {
            DB::table('institutions')->insertOrIgnore([
                'id' => 2,
                'name' => 'SMP Islam Baitul Makmur',
                'slug' => 'smp',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Proteksi penuh: Jangan pernah menimpa jika sudah ada data penugasan guru di database
        $tscCount = DB::table('teacher_subject_classes')->count();
        if ($tscCount === 0) {
            RestoreCurriculumScheduleCommand::restoreSchedule(null, false);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
