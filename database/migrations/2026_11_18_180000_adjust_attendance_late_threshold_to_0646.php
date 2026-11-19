<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Koreksi catatan absensi siswa yang hadir pada pukul 06:45 (sebelum 06:46:00) agar statusnya tidak terlambat
        DB::table('daily_attendances')
            ->where('tipe_absen', 'masuk')
            ->where('status', 'hadir')
            ->whereNotNull('waktu_kedatangan')
            ->whereTime('waktu_kedatangan', '<', '06:46:00')
            ->where('is_late', true)
            ->update([
                'is_late' => false,
                'late_penalty_points' => 0,
                'late_approval_status' => null,
            ]);
    }

    public function down(): void
    {
        // Tidak perlu rollback perubahan data
    }
};
