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
        // Tabel Utama: Laporan Perawatan Berkala Sarpras (Input Bulanan)
        Schema::create('perawatan_berkala_sarpras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Petugas / Staf Sarpras pelapor
            $table->foreignId('penempatan_sarpras_id')->nullable()->constrained('penempatan_sarpras')->nullOnDelete(); // Ruangan spesifik atau null jika multi-lokasi

            // Informasi Utama Laporan Perawatan
            $table->string('nomor_laporan')->nullable(); // Contoh: RWT/2026/10/0001
            $table->string('nama_kegiatan'); // Contoh: Pemeliharaan Rutin Bulanan Sarpras & Elektronik Lab Komputer
            $table->string('periode_bulan'); // Januari, Februari, ..., Oktober, Desember
            $table->integer('periode_tahun')->default(date('Y')); // 2026
            $table->date('tanggal_pelaksanaan');
            $table->date('tanggal_selesai')->nullable();
            $table->string('jenis_pemeliharaan')->default('Perawatan Rutin Bulanan'); // Perawatan Rutin Bulanan, Pemeliharaan Pencegahan (Preventive), Perbaikan Ringan (Corrective), Servis Berkala Teknisi, Kalibrasi & Sanitasi
            $table->string('lokasi_ruangan')->nullable(); // Nama ruangan atau 'Seluruh Lingkungan Sekolah'
            
            // Pelaksana & Anggaran
            $table->string('petugas_pelaksana')->nullable(); // Nama Staf Sarpras / Teknisi Rekanan
            $table->string('kontak_teknisi')->nullable(); // No HP / Kontak teknisi/rekanan
            $table->string('sumber_dana')->default('Anggaran Rutin Sarpras'); // Anggaran Rutin Sarpras, SPP / Komite, Yayasan, Lainnya
            
            // Rekapitulasi Otomatis Hasil Pemeriksaan & Servis
            $table->integer('total_item_diperiksa')->default(0); // Jumlah ragam jenis barang
            $table->integer('total_unit_diperiksa')->default(0); // Total unit fisik yang dicek/diservis
            $table->integer('total_kondisi_baik')->default(0); // Unit kondisi baik setelah perawatan
            $table->integer('total_kondisi_rusak_ringan')->default(0); // Unit masih rusak ringan
            $table->integer('total_kondisi_rusak_berat')->default(0); // Unit rusak berat / butuh penggantian
            $table->decimal('total_biaya_pemeliharaan', 15, 2)->default(0); // Akumulasi biaya servis / sparepart

            // Status Perawatan & Evaluasi
            $table->string('status_perawatan')->default('Selesai'); // Terjadwal, Dalam Pengerjaan, Selesai, Perlu Tindak Lanjut Khusus
            $table->text('catatan_umum')->nullable(); // Catatan umum / rekomendasi sarpras ke depan

            // Dokumentasi Berkas / Foto via Google Drive (hemat penyimpanan hosting)
            $table->text('link_drive_dokumentasi')->nullable(); // Link folder Google Drive foto sebelum-sesudah & bukti kuitansi servis

            // Alur Persetujuan Kepala Sekolah (Approval)
            $table->string('status_persetujuan')->default('Menunggu Persetujuan'); // Menunggu Persetujuan, Disetujui, Perlu Perbaikan, Ditolak
            $table->text('catatan_kepsek')->nullable(); // Catatan/disposisi dari Kepala Sekolah
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // Kepala Sekolah yang menyetujui
            $table->dateTime('tanggal_persetujuan')->nullable();

            $table->timestamps();
        });

        // Tabel Rincian: Item Sarana & Inventaris yang Dirawat (Terkoneksi ke Master Inventaris)
        Schema::create('perawatan_berkala_sarpras_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perawatan_berkala_id')->constrained('perawatan_berkala_sarpras')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete(); // Terhubung ke Master Inventaris

            $table->integer('jumlah_unit')->default(1); // Jumlah unit barang yang diperiksa
            $table->string('kondisi_sebelum')->default('Baik'); // Baik, Rusak Ringan, Rusak Berat
            $table->string('tindakan_perawatan')->default('Pembersihan & Pengecekan Fungsi Rutin'); // Pembersihan & Pengecekan Fungsi Rutin, Perbaikan Ringan / Servis Mandiri, Penggantian Komponen / Sparepart, Servis Teknisi Luar / Vendor, Kalibrasi & Pengaturan Ulang, Pemberian Pelumas / Maintenance Fisik, Lainnya
            $table->text('rincian_tindakan')->nullable(); // Contoh: Pembersihan debu filter, penambahan freon AC, pengetesan arus listrik
            $table->string('kondisi_setelah')->default('Baik'); // Baik, Rusak Ringan, Rusak Berat
            $table->decimal('biaya', 15, 2)->default(0); // Biaya jasa / sparepart untuk item ini
            $table->text('keterangan')->nullable(); // Keterangan tambahan atau serial number barang

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perawatan_berkala_sarpras_items');
        Schema::dropIfExists('perawatan_berkala_sarpras');
    }
};
