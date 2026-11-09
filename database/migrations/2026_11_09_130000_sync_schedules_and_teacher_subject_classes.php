<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;
use App\Models\TeacherSubjectClass;
use App\Models\Classroom;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Normalisasi institution_id pada tabel schedules
        $schedules = DB::table('schedules')->whereNull('institution_id')->orWhere('institution_id', 0)->get();
        foreach ($schedules as $sch) {
            $instId = 2;
            if ($sch->classroom_id) {
                $classroom = DB::table('classrooms')->where('id', $sch->classroom_id)->first();
                if ($classroom && $classroom->institution_id) {
                    $instId = $classroom->institution_id;
                }
            }
            DB::table('schedules')->where('id', $sch->id)->update(['institution_id' => $instId]);
        }

        // 2. Normalisasi institution_id pada tabel teacher_subject_classes
        $tscs = DB::table('teacher_subject_classes')->whereNull('institution_id')->orWhere('institution_id', 0)->get();
        foreach ($tscs as $tsc) {
            $instId = 2;
            if ($tsc->classroom_id) {
                $classroom = DB::table('classrooms')->where('id', $tsc->classroom_id)->first();
                if ($classroom && $classroom->institution_id) {
                    $instId = $classroom->institution_id;
                }
            }
            DB::table('teacher_subject_classes')->where('id', $tsc->id)->update(['institution_id' => $instId]);
        }

        // 3. Sinkronisasi dari schedules ke teacher_subject_classes
        $validSchedules = DB::table('schedules')
            ->whereNotNull('teacher_id')
            ->whereNotNull('subject_id')
            ->whereNotNull('classroom_id')
            ->select('teacher_id', 'subject_id', 'classroom_id', 'institution_id')
            ->distinct()
            ->get();

        foreach ($validSchedules as $vs) {
            $exists = DB::table('teacher_subject_classes')
                ->where('subject_id', $vs->subject_id)
                ->where('classroom_id', $vs->classroom_id)
                ->exists();

            if (!$exists) {
                DB::table('teacher_subject_classes')->insert([
                    'teacher_id'     => $vs->teacher_id,
                    'subject_id'     => $vs->subject_id,
                    'classroom_id'   => $vs->classroom_id,
                    'institution_id' => $vs->institution_id ?: 2,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        // 4. Jika schedules kosong dan institusi SMP (id=2) sudah ada, pulihkan jadwal kurikulum otomatis
        $smpExists = DB::table('institutions')->where('id', 2)->exists();
        $smpScheduleCount = DB::table('schedules')->where('institution_id', 2)->count();
        if ($smpExists && $smpScheduleCount === 0) {
            \App\Console\Commands\RestoreCurriculumScheduleCommand::restoreSchedule();
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
