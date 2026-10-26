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
        // Tabel Utama: Rencana Kebutuhan Sarpras
        Schema::create('rencana_kebutuhan_sarpras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Staf Sarpras yang menyusun

            // Informasi Dokumen Rencana Kebutuhan
            $table->string('nomor_rencana')->nullable()->unique(); // Contoh: RKB/SMP/2026/X/0001
            $table->string('nama_rencana'); // Contoh: Perencanaan Kebutuhan Sarpras Semester Ganjil TA 2026/2027
            $table->string('tahun_ajaran')->default('2026/2027');
            $table->string('semester')->default('Ganjil'); // Ganjil, Genap, Tahunan, Insidental / Mendesak
            $table->date('tanggal_pengajuan')->default(now()->toDateString());
            $table->string('skala_prioritas')->default('Sedang'); // Sangat Mendesak, Mendesak, Sedang, Rendah / Rutin
            $table->string('sumber_anggaran')->default('Yayasan / SPP'); // Yayasan / SPP, Anggaran Rutin Sarpras, Komite / Infaq, Donatur / Hibah, Lainnya
            $table->string('target_waktu_pengadaan')->nullable(); // Contoh: November 2026 / Awal Semester Genap
            
            // Rekapitulasi Anggaran & Item
            $table->decimal('total_estimasi_anggaran', 15, 2)->default(0);
            $table->integer('total_item')->default(0);

            // Penjelasan & Justifikasi Kebutuhan
            $table->text('tujuan_manfaat'); // Latar belakang, urgensi, dan manfaat kebutuhan bagi KBM & sekolah
            $table->text('link_drive_dokumen')->nullable(); // Link Google Drive: proposal teknis, surat penawaran vendor, foto eksisting

            // Status Pelaksanaan & Pengadaan
            $table->string('status_pelaksanaan')->default('Draft'); // Draft, Diajukan, Disetujui untuk Pengadaan, Sedang Proses Pengadaan, Terealisasi, Dibatalkan

            // Integrasi Persetujuan Kepala Sekolah
            $table->string('status_persetujuan')->default('Menunggu Persetujuan'); // Menunggu Persetujuan, Disetujui, Perlu Perbaikan, Ditolak
            $table->text('catatan_kepsek')->nullable(); // Catatan arahan / instruksi dari Kepala Sekolah
            $table->dateTime('tanggal_persetujuan')->nullable(); // Waktu saat di-ACC / ditinjau
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // Kepala Sekolah yang meng-ACC

            $table->timestamps();
        });

        // Tabel Rincian Item: Rencana Kebutuhan Sarpras Items
        Schema::create('rencana_kebutuhan_sarpras_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rencana_kebutuhan_sarpras_id')->constrained('rencana_kebutuhan_sarpras', indexName: 'fk_rkb_items_parent')->cascadeOnDelete();
            
            $table->string('nama_barang'); // Contoh: Proyektor LCD Epson, AC Split 1 PK, Meja Siswa
            $table->string('kategori'); // Meubeler, Elektronik/TIK, Alat Peraga, Perlengkapan Ibadah, dll
            $table->text('spesifikasi')->nullable(); // Merk, tipe, ukuran, detail teknis
            $table->integer('jumlah')->default(1);
            $table->string('satuan')->default('Unit'); // Unit, Buah, Set, Paket, Pcs, Rim, dll
            $table->decimal('estimasi_harga_satuan', 15, 2)->default(0);
            $table->decimal('subtotal_biaya', 15, 2)->default(0);
            
            $table->string('prioritas_item')->default('Sedang'); // Sangat Mendesak, Mendesak, Sedang, Pelengkap
            $table->string('target_lokasi')->nullable(); // Contoh: Lab Komputer, Kelas 7A, Ruang Guru
            $table->text('alasan_kebutuhan')->nullable(); // Contoh: Unit lama rusak berat, penambahan rombel baru

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rencana_kebutuhan_sarpras_items');
        Schema::dropIfExists('rencana_kebutuhan_sarpras');
    }
};
