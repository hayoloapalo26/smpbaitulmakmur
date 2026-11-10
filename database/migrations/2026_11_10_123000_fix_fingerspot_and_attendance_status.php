<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Pastikan kolom id_finger dan fingerspot_pin pada tabel students
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'id_finger')) {
                $table->string('id_finger', 50)->nullable()->after('nis')->index();
            }
            if (!Schema::hasColumn('students', 'fingerspot_pin')) {
                $table->string('fingerspot_pin', 50)->nullable()->after('id_finger')->index();
            }
        });

        // 2. Pastikan kolom id_finger dan fingerspot_pin pada tabel users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'id_finger')) {
                $table->string('id_finger', 50)->nullable()->after('no_induk_yayasan')->index();
            }
            if (!Schema::hasColumn('users', 'fingerspot_pin')) {
                $table->string('fingerspot_pin', 50)->nullable()->after('id_finger')->index();
            }
        });

        // 3. Ubah status daily_attendances menjadi string agar fleksibel (hadir, izin, sakit, alpa, halangan)
        try {
            Schema::table('daily_attendances', function (Blueprint $table) {
                $table->string('status', 50)->default('hadir')->change();
            });
        } catch (\Throwable $e) {
            // Abaikan jika database engine tidak memerlukan change
        }

        // 4. Ubah status teacher_attendances menjadi string agar kompatibel dengan 'masuk' dan 'hadir'
        try {
            Schema::table('teacher_attendances', function (Blueprint $table) {
                $table->string('status', 50)->default('masuk')->change();
            });
        } catch (\Throwable $e) {
            // Abaikan jika database engine tidak memerlukan change
        }

        // 5. Perbaiki data presensi siswa yang sebelumnya tersimpan dengan institution_id = 1 (Yayasan) menjadi 2 (SMP)
        try {
            DB::statement("
                UPDATE daily_attendances da
                JOIN students s ON da.student_id = s.id
                SET da.institution_id = COALESCE(s.institution_id, 2)
                WHERE da.institution_id = 1 OR da.institution_id IS NULL
            ");
        } catch (\Throwable $e) {}

        // 6. Sinkronisasi id_finger dan fingerspot_pin pada tabel students
        try {
            DB::statement("UPDATE students SET id_finger = fingerspot_pin WHERE fingerspot_pin IS NOT NULL AND (id_finger IS NULL OR id_finger = '')");
            DB::statement("UPDATE students SET fingerspot_pin = id_finger WHERE id_finger IS NOT NULL AND (fingerspot_pin IS NULL OR fingerspot_pin = '')");
        } catch (\Throwable $e) {}

        // 7. Sinkronisasi id_finger dan fingerspot_pin pada tabel users
        try {
            DB::statement("UPDATE users SET id_finger = fingerspot_pin WHERE fingerspot_pin IS NOT NULL AND (id_finger IS NULL OR id_finger = '')");
            DB::statement("UPDATE users SET fingerspot_pin = id_finger WHERE id_finger IS NOT NULL AND (fingerspot_pin IS NULL OR fingerspot_pin = '')");
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive reverse needed
    }
};
