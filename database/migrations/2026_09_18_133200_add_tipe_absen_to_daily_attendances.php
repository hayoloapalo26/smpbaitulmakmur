<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename kolom lama dan tambah kolom baru untuk 4 slot absen
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->string('tipe_absen')->default('masuk')->after('teacher_id');
            // tipe_absen: masuk | sholat_dhuhur | sholat_ashar | pulang
            $table->string('metode_absen')->default('manual')->after('tipe_absen');
            // metode_absen: manual | scan_barcode | fingerprint
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->dropColumn(['tipe_absen', 'metode_absen']);
        });
    }
};
