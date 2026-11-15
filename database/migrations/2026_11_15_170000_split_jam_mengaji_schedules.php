<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Pecah jam mengaji menjadi per-JP (Jam Mengaji 1: key -1 dan Jam Mengaji 2: key 0).
     * Jika ada jadwal lama dengan jam_ke = 0, pastikan slot jam_ke = -1 juga tersedia
     * agar guru mengaji mendapatkan alokasi 2 JP penuh (07.00 - 08.10).
     */
    public function up(): void
    {
        $existing = DB::table('schedules')->where('jam_ke', 0)->get();

        foreach ($existing as $sched) {
            $hasMengaji1 = DB::table('schedules')
                ->where('classroom_id', $sched->classroom_id)
                ->where('teacher_id', $sched->teacher_id)
                ->where('subject_id', $sched->subject_id)
                ->where('hari', $sched->hari)
                ->where('jam_ke', -1)
                ->exists();

            if (!$hasMengaji1) {
                DB::table('schedules')->insert([
                    'classroom_id'   => $sched->classroom_id,
                    'teacher_id'     => $sched->teacher_id,
                    'subject_id'     => $sched->subject_id,
                    'hari'           => $sched->hari,
                    'jam_ke'         => -1,
                    'institution_id' => $sched->institution_id ?? 2,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive reverse needed
    }
};
