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
        Schema::create('class_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('wali_kelas_id')->constrained('users')->cascadeOnDelete();
            $table->string('tahun_ajaran')->default('2026/2027');
            $table->string('semester')->default('Ganjil'); // Ganjil, Genap
            $table->string('nama_program');
            $table->string('kategori')->default('administrasi'); // administrasi, akademik, karakter_disiplin, paguyuban_ortu, lingkungan_sarpras, kegiatan_kreatif, lainnya
            $table->string('tipe_pelaksanaan')->default('bulanan'); // harian, mingguan, bulanan, semesteran, tahunan, insidental
            $table->text('tujuan')->nullable();
            $table->string('sasaran')->nullable();
            $table->string('waktu_pelaksanaan')->nullable();
            $table->bigInteger('estimasi_anggaran')->nullable()->default(0);
            $table->string('status')->default('rencana'); // rencana, berjalan, terlaksana, tertunda, batal
            $table->text('evaluasi_catatan')->nullable();
            $table->string('dokumen_pendukung')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_programs');
    }
};
