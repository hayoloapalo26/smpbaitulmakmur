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
        Schema::create('evaluasi_program_sarpras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Staf Sarpras yang menginput evaluasi
            $table->foreignId('program_sarpras_id')->nullable()->constrained('program_sarpras')->nullOnDelete(); // Referensi Program Kerja Sarpras (yang di-ACC)
            $table->string('source_type')->default('App\\Models\\ProgramSarpras'); // Bisa ProgramSarpras atau RencanaKebutuhanSarpras
            $table->unsignedBigInteger('source_id')->nullable();

            // Identitas Dokumen & Program yang Dievaluasi
            $table->string('nomor_evaluasi')->nullable(); // Contoh: EVAL-SARPRAS/2026/10/0001
            $table->string('nama_program'); // Nama program kerja yang dievaluasi
            $table->string('kategori_program'); // Kategori (Pengadaan, Pemeliharaan, Inventarisasi, dll)
            $table->string('tahun_ajaran'); // 2026/2027
            $table->string('waktu_pelaksanaan')->nullable(); // Jadwal pelaksanaan awal
            $table->date('tanggal_evaluasi')->default(now()->toDateString()); // Tanggal pelaporan evaluasi

            // Anggaran
            $table->decimal('estimasi_anggaran', 15, 2)->default(0); // Anggaran yang direncanakan
            $table->decimal('realisasi_anggaran', 15, 2)->default(0); // Anggaran yang terealisasi

            // Ketercapaian & Kinerja
            $table->string('status_keterlaksanaan')->default('Terlaksana Sepenuhnya'); // Terlaksana Sepenuhnya, Terlaksana Sebagian, Tidak Terlaksana / Tertunda, Dibatalkan
            $table->integer('persentase_ketercapaian')->default(100); // 0 - 100%
            $table->string('predikat_capaian')->default('Sangat Baik'); // Sangat Baik (>=90%), Baik (75-89%), Cukup (60-74%), Kurang (<60%)
            $table->text('deskripsi_ketercapaian'); // Uraian faktual hasil realisasi fisik sarana/prasarana
            $table->text('kendala_hambatan')->nullable(); // Kendala teknis, penganggaran, pengiriman barang, atau vendor
            $table->text('solusi_tindak_lanjut')->nullable(); // Upaya pemecahan kendala & tindak lanjut
            $table->text('rekomendasi_periode_berikutnya')->nullable(); // Saran perbaikan untuk program kerja tahun berikutnya

            // Dokumentasi Google Drive (Hemat Penyimpanan Hosting)
            $table->text('link_drive_lpj')->nullable(); // Link folder Google Drive dokumen LPJ, foto hasil fisik sarpras, atau bukti kuitansi belanja

            // Tinjauan & Disposisi Kepala Sekolah
            $table->string('status_review')->default('Menunggu Review'); // Menunggu Review, Disetujui (Selesai Dievaluasi), Perlu Perbaikan Laporan
            $table->text('catatan_kepsek')->nullable(); // Arahan, evaluasi, atau apresiasi Kepala Sekolah
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); // Kepala Sekolah peninjau
            $table->dateTime('tanggal_review')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluasi_program_sarpras');
    }
};
