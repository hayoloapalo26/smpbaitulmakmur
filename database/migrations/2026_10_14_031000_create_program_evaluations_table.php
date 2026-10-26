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
        Schema::create('program_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('tahun_ajaran')->default('2026/2027');
            $table->string('semester')->default('Semester Ganjil');
            $table->string('divisi');
            $table->string('nama_program');
            
            $table->integer('persentase_ketercapaian')->default(0);
            $table->string('predikat')->default('Baik');
            
            $table->text('realisasi_kegiatan')->nullable();
            $table->text('kendala')->nullable();
            $table->text('solusi_tindak_lanjut')->nullable();
            $table->text('catatan_kepsek')->nullable();
            
            $table->string('file_evaluasi')->nullable();
            $table->string('status')->default('Final');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_evaluations');
    }
};
