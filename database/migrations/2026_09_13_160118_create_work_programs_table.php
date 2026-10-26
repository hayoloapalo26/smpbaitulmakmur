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
        Schema::create('work_programs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_program');
            $table->enum('divisi', ['kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'sarpras', 'humas']);
            $table->foreignId('penanggung_jawab_id')->constrained('users')->onDelete('cascade');
            
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            
            $table->enum('status_pengajuan', ['draft', 'diajukan', 'disetujui_ks', 'ditolak'])->default('draft');
            $table->enum('status_pelaksanaan', ['mendatang', 'berjalan', 'selesai'])->default('mendatang');
            
            $table->text('target_pencapaian')->nullable();
            
            // Penilaian dan Laporan
            $table->integer('skor_penilaian')->nullable();
            $table->text('catatan_ks')->nullable();
            $table->enum('laporan_yayasan', ['tidak', 'mingguan', 'bulanan', 'tahunan'])->default('tidak');
            
            $table->string('file_proposal')->nullable();
            $table->string('file_lpj')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_programs');
    }
};
