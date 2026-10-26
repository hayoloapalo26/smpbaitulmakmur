<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_sarpras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tahun_ajaran');
            $table->string('nama_program');
            $table->string('kategori');
            $table->string('tipe_pelaksanaan');
            $table->string('sifat');
            $table->text('tujuan');
            $table->text('teknis_pelaksanaan')->nullable();
            $table->string('sasaran')->nullable();
            $table->string('waktu_pelaksanaan')->nullable();
            $table->string('dokumen_terkait')->nullable();
            $table->string('estimasi_anggaran')->nullable();
            $table->string('status_pelaksanaan')->default('Belum');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_sarpras');
    }
};
