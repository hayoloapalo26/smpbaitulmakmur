<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('school_accreditations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // 1. No / Nomor Akreditasi / SK
            $table->string('no')->nullable();
            $table->string('nomor_akreditasi');

            // 2. Masa Berlaku sampai tahun berapa
            $table->integer('masa_berlaku_sampai_tahun');

            // 3. Hasil Akreditasi
            $table->string('hasil_akreditasi');

            // 5. Nama Assesor 1
            $table->string('nama_assesor_1');

            // 6. Nama Assesor 2
            $table->string('nama_assesor_2')->nullable();

            // 7. Link dokumen yang sudah di drive
            $table->text('link_dokumen_drive');

            // Optional complementary fields
            $table->integer('nilai_angka')->nullable();
            $table->date('tanggal_sk')->nullable();
            $table->string('status')->default('Aktif');
            $table->text('keterangan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_accreditations');
    }
};
