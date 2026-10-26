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
        Schema::create('hubungan_institusis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Inputan kegiatan hubungan institusi (kemitraan, kunjungan dinas, MoU, studi banding, dll)
            $table->string('nama_kegiatan'); // Contoh: Penandatanganan MoU dengan Universitas, Kunjungan Dinas Pendidikan
            $table->string('target_peserta'); // Contoh: Dinas Pendidikan Kota, Perguruan Tinggi Mitra, Puskesmas, Dunia Usaha/Industri
            $table->date('tanggal_pelaksanaan'); // Tanggal kegiatan
            $table->string('lokasi_kegiatan'); // Lokasi pelaksanaan
            $table->text('tujuan_kegiatan'); // Tujuan dan bentuk kerja sama
            $table->string('status_pelaksanaan')->default('Rencana'); // Rencana, Terjadwal, Sedang Berjalan, Selesai, Batal
            $table->text('link_drive_dokumen')->nullable(); // Tautan Google Drive dokumen pendukung (Draft MoU, Surat Kerja Sama, Proposal, LPJ, Foto, dll)
            
            // Integrasi persetujuan Kepala Sekolah (ACC)
            $table->string('status_persetujuan')->default('Menunggu Persetujuan'); // Menunggu Persetujuan, Disetujui, Perlu Perbaikan, Ditolak
            $table->text('catatan_kepsek')->nullable(); // Catatan atau masukan dari Kepala Sekolah
            $table->dateTime('tanggal_persetujuan')->nullable(); // Waktu respon persetujuan
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // Kepala Sekolah yang menyetujui
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hubungan_institusis');
    }
};
