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
        // 1. Kolom ID Finger untuk Guru / Pegawai (Tabel users)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'id_finger')) {
                $table->string('id_finger', 50)->nullable()->unique()->after('no_induk_yayasan');
            }
            if (!Schema::hasColumn('users', 'fingerspot_pin')) {
                $table->string('fingerspot_pin', 50)->nullable()->unique()->after('id_finger');
            }
        });

        // 2. Kolom ID Finger untuk Siswa / Santri (Tabel students)
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'id_finger')) {
                $table->string('id_finger', 50)->nullable()->after('nis');
            }
            if (!Schema::hasColumn('students', 'fingerspot_pin')) {
                $table->string('fingerspot_pin', 50)->nullable()->after('id_finger');
            }
        });

        // Sinkronisasi data awal siswa jika fingerspot_pin sudah terisi
        try {
            DB::statement("UPDATE students SET id_finger = fingerspot_pin WHERE fingerspot_pin IS NOT NULL AND (id_finger IS NULL OR id_finger = '')");
            DB::statement("UPDATE students SET fingerspot_pin = id_finger WHERE id_finger IS NOT NULL AND (fingerspot_pin IS NULL OR fingerspot_pin = '')");
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'id_finger')) {
                $table->dropColumn('id_finger');
            }
            if (Schema::hasColumn('users', 'fingerspot_pin')) {
                $table->dropColumn('fingerspot_pin');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'id_finger')) {
                $table->dropColumn('id_finger');
            }
        });
    }
};
