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
        Schema::create('harmonisasi_internals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Inputan kegiatan harmonisasi
            $table->string('nama_kegiatan'); // Contoh: Family Gathering, Outbound, dll
            $table->string('target_peserta'); // Contoh: Seluruh Guru & Karyawan
            $table->date('tanggal_pelaksanaan'); // Tanggal kegiatan
            $table->string('lokasi_kegiatan'); // Lokasi pelaksanaan
            $table->text('tujuan_kegiatan'); // Tujuan kegiatan
            $table->string('status_pelaksanaan')->default('Rencana'); // Rencana, Terjadwal, Sedang Berjalan, Selesai, Batal
            $table->text('link_drive_dokumen')->nullable(); // Tautan Google Drive dokumen pendukung
            
            // Integrasi persetujuan Kepala Sekolah
            $table->string('status_persetujuan')->default('Menunggu Persetujuan'); // Menunggu Persetujuan, Disetujui, Perlu Perbaikan, Ditolak
            $table->text('catatan_kepsek')->nullable(); // Catatan atau alasan dari Kepala Sekolah
            $table->dateTime('tanggal_persetujuan')->nullable(); // Waktu saat di-ACC / ditinjau
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // Kepala Sekolah yang meng-ACC
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harmonisasi_internals');
    }
};
