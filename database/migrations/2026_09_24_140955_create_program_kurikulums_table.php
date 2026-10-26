<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_kurikulums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tahun_ajaran')->default('2026/2027');
            $table->string('nama_program');
            $table->string('kategori'); 
            $table->string('tipe_pelaksanaan'); // Harian, Mingguan, Bulanan, Semesteran, Tahunan
            $table->string('sifat'); // Wajib, Kontinu, Terjadwal
            $table->text('tujuan')->nullable();
            $table->text('teknis_pelaksanaan')->nullable();
            $table->string('sasaran')->nullable();
            $table->string('waktu_pelaksanaan')->nullable();
            $table->string('dokumen_terkait')->nullable();
            $table->integer('estimasi_anggaran')->nullable();
            $table->string('status_pelaksanaan')->default('Rencana');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_kurikulums');
    }
};
