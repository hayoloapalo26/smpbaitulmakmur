<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Institution;
use App\Models\User;
use App\Models\Schedule;
use App\Models\TeacherAttendance;
use App\Models\Guardian;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengisolasi 100% data seluruh akun guru, jadwal, presensi, dan data orang tua per lembaga (SD vs SMP).
     */
    public function up(): void
    {
        // 1. Pastikan entitas Institusi terdaftar tepat
        $sd = Institution::withoutGlobalScopes()->where('id', 4)->first();
        if (!$sd) {
            $sd = Institution::withoutGlobalScopes()->where('slug', 'sd')->first();
        }
        $sdId = $sd ? $sd->id : 4;

        $smp = Institution::withoutGlobalScopes()->where('id', 2)->first();
        if (!$smp) {
            $smp = Institution::withoutGlobalScopes()->where('slug', 'smp')->first();
        }
        $smpId = $smp ? $smp->id : 2;

        // 2. Isolasi User SD: Semua akun dengan nama/email/username SD wajib memiliki institution_id = $sdId
        $sdUsers = User::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('name', 'like', '%SD Islam%')
                  ->orWhere('name', 'like', '% SD%')
                  ->orWhere('email', 'like', '%_sd@%')
                  ->orWhere('email', 'like', '%@sd%')
                  ->orWhere('username', 'like', '%_sd');
            })
            ->get();

        $sdUserIds = [];
        foreach ($sdUsers as $u) {
            $sdUserIds[] = $u->id;
            DB::table('users')->where('id', $u->id)->update(['institution_id' => $sdId]);

            if (Schema::hasTable('institution_user')) {
                DB::table('institution_user')->where('user_id', $u->id)->delete();
                DB::table('institution_user')->insert([
                    'user_id' => $u->id,
                    'institution_id' => $sdId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Isolasi User SMP: Semua akun SMP wajib memiliki institution_id = $smpId
        $smpUsers = User::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('name', 'like', '%SMP Islam%')
                  ->orWhere('name', 'like', '% SMP%')
                  ->orWhere('email', 'like', '%_smp@%')
                  ->orWhere('email', 'like', '%@smp%')
                  ->orWhere('username', 'like', '%_smp');
            })
            ->get();

        $smpUserIds = [];
        foreach ($smpUsers as $u) {
            $smpUserIds[] = $u->id;
            DB::table('users')->where('id', $u->id)->update(['institution_id' => $smpId]);

            if (Schema::hasTable('institution_user')) {
                DB::table('institution_user')->where('user_id', $u->id)->delete();
                DB::table('institution_user')->insert([
                    'user_id' => $u->id,
                    'institution_id' => $smpId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 4. Update schedules: jadwal guru SD masuk ke $sdId, jadwal guru SMP masuk ke $smpId
        if (!empty($sdUserIds) && Schema::hasTable('schedules')) {
            DB::table('schedules')->whereIn('teacher_id', $sdUserIds)->update(['institution_id' => $sdId]);
        }
        if (!empty($smpUserIds) && Schema::hasTable('schedules')) {
            DB::table('schedules')->whereIn('teacher_id', $smpUserIds)->update(['institution_id' => $smpId]);
        }

        // 5. Update teacher_attendances: presensi guru SD ke $sdId, SMP ke $smpId
        if (!empty($sdUserIds) && Schema::hasTable('teacher_attendances')) {
            DB::table('teacher_attendances')->whereIn('teacher_id', $sdUserIds)->update(['institution_id' => $sdId]);
        }
        if (!empty($smpUserIds) && Schema::hasTable('teacher_attendances')) {
            DB::table('teacher_attendances')->whereIn('teacher_id', $smpUserIds)->update(['institution_id' => $smpId]);
        }

        // 6. Sinkronisasi data orang tua (guardians) dengan institution_id siswa terkait
        if (Schema::hasTable('guardians') && Schema::hasTable('student_guardians') && Schema::hasTable('students')) {
            $guardians = Guardian::withoutGlobalScopes()->whereNull('institution_id')->get();
            foreach ($guardians as $g) {
                $studentInstId = DB::table('student_guardians')
                    ->join('students', 'student_guardians.student_id', '=', 'students.id')
                    ->where('student_guardians.guardian_id', $g->id)
                    ->value('students.institution_id');

                if ($studentInstId) {
                    $g->update(['institution_id' => $studentInstId]);
                } else {
                    $g->update(['institution_id' => $smpId]);
                }
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
