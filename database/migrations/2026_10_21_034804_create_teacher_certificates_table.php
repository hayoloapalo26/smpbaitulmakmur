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
        Schema::create('teacher_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->string('nama_kegiatan');
            $table->string('penyelenggara');
            $table->string('kategori')->default('Pelatihan / Bimtek'); // Pelatihan / Bimtek, Workshop / Lokakarya, Webinar / Seminar, Sertifikasi Kompetensi, Diklat / Kursus, Penghargaan / Lomba, Lainnya
            $table->date('tanggal_pelaksanaan');
            $table->string('nomor_sertifikat')->nullable();
            $table->integer('jumlah_jam')->nullable(); // Dalam JP (Jam Pelajaran)
            $table->text('drive_url'); // Link Google Drive Sertifikat
            $table->text('keterangan')->nullable(); // Materi / ringkasan pelatihan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_certificates');
    }
};
