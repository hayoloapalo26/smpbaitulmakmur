<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Perbaiki record absensi santri laki-laki di hari Jumat yang sempat salah masuk ke sesi 'masuk' pada rentang waktu jam 11:00 s.d 14:29
        // Ubah menjadi sesi 'sholat_dhuhur' (Sholat Jum'at), tepat waktu (is_late = false), late_penalty_points = 0
        try {
            $affected = DB::table('daily_attendances')
                ->join('students', 'daily_attendances.student_id', '=', 'students.id')
                ->where('students.jenis_kelamin', 'L')
                ->whereRaw('DAYOFWEEK(daily_attendances.tanggal) = 6') // 6 = Friday di MySQL
                ->where('daily_attendances.tipe_absen', 'masuk')
                ->where('daily_attendances.waktu_kedatangan', '>=', '11:00:00')
                ->where('daily_attendances.waktu_kedatangan', '<', '14:30:00')
                ->update([
                    'daily_attendances.tipe_absen'          => 'sholat_dhuhur',
                    'daily_attendances.is_late'             => false,
                    'daily_attendances.late_penalty_points' => 0,
                    'daily_attendances.late_approval_status'=> null,
                    'daily_attendances.keterangan'          => DB::raw("CONCAT('Sholat Jum\'at - Tepat Waktu (', SUBSTRING(daily_attendances.waktu_kedatangan, 1, 5), ') via Fingerspot')"),
                ]);

            Log::info("Migration fix_friday_jumat_attendance_scans: {$affected} record dipindahkan ke Sholat Jum'at");
        } catch (\Throwable $e) {
            Log::warning("Migration fix_friday_jumat_attendance_scans DB update notice: " . $e->getMessage());
        }

        // 2. Otomatis picu sinkronisasi data Fingerspot Cloud hari ini agar scan jam 11:00 santri putra langsung masuk
        try {
            Artisan::call('fingerspot:sync');
            Log::info("Migration fix_friday_jumat_attendance_scans: fingerspot:sync dipanggil otomatis");
        } catch (\Throwable $e) {
            Log::warning("Migration fix_friday_jumat_attendance_scans fingerspot:sync notice: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed
    }
};
