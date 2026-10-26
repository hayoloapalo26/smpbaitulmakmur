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
        // Tabel Utama: Penempatan Sarpras & Ruangan (Kartu Inventaris Ruangan / Lokasi)
        Schema::create('penempatan_sarpras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Staf Sarpras pencatat

            // Informasi Ruangan
            $table->string('kode_ruangan')->nullable(); // Contoh: R-7A, LAB-KOMP-1, RG-01
            $table->string('nama_ruangan'); // Contoh: Ruang Kelas 7A, Lab Komputer 1, Ruang Guru
            $table->string('kategori_ruangan')->default('Ruang Kelas'); // Ruang Kelas, Laboratorium, Ruang Guru, Ruang Pimpinan, Perpustakaan, Tempat Ibadah, UKS, Gudang, dll
            $table->string('gedung_lantai')->nullable(); // Contoh: Gedung A, Lantai 2
            $table->string('luas_ruangan')->nullable(); // Contoh: 7 x 8 m² (56 m²)

            // Penanggung Jawab Ruangan & Aset
            $table->foreignId('penanggung_jawab_id')->nullable()->constrained('users')->nullOnDelete(); // User guru / wali kelas / kepala lab
            $table->string('nama_penanggung_jawab')->nullable(); // Nama lengkap PJ
            $table->string('jabatan_pj')->nullable(); // Contoh: Wali Kelas 7A, Kepala Lab Komputer
            $table->string('kontak_pj')->nullable(); // No. WhatsApp / Telepon PJ

            // Dokumentasi Visual & Kartu Inventaris Ruangan (KIR) via Google Drive (hemat hosting)
            $table->text('link_drive_foto')->nullable(); // Link Google Drive foto tata letak fisik ruangan
            $table->text('link_drive_kir')->nullable(); // Link Google Drive berkas Kartu Inventaris Ruangan (KIR) bertanda tangan

            // Rekapitulasi Otomatis Barang di Ruangan Ini
            $table->integer('total_item_sarpras')->default(0); // Berapa ragam jenis barang
            $table->integer('total_unit_sarpras')->default(0); // Total unit fisik keseluruhan
            $table->decimal('total_nilai_aset', 15, 2)->default(0); // Total akumulasi nilai aset di ruangan ini

            // Status & Keterangan
            $table->string('status_ruangan')->default('Aktif / Digunakan'); // Aktif / Digunakan, Dalam Perbaikan / Renovasi, Tidak Aktif
            $table->text('keterangan')->nullable(); // Catatan fasilitas / tata tertib ruangan

            $table->timestamps();
        });

        // Tabel Rincian: Barang-barang yang Ditempatkan di Ruangan Ini (Terkoneksi ke Master Inventaris)
        Schema::create('penempatan_sarpras_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penempatan_sarpras_id')->constrained('penempatan_sarpras')->cascadeOnDelete();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete(); // Terhubung langsung ke Master Inventaris

            $table->integer('jumlah')->default(1); // Jumlah unit yang dialokasikan ke ruangan ini
            $table->string('satuan')->default('Unit'); // Diambil otomatis dari Master Inventaris
            $table->string('kondisi')->default('Baik'); // Baik, Rusak Ringan, Rusak Berat
            $table->string('posisi_spesifik')->nullable(); // Contoh: Di meja guru depan, Di rak lemari sudut kiri
            $table->date('tanggal_penempatan')->default(now()->toDateString());
            $table->decimal('subtotal_nilai_aset', 15, 2)->default(0); // Kalkulasi jumlah * harga_perolehan master
            $table->text('keterangan')->nullable(); // Catatan riwayat mutasi / kondisi khusus barang di ruangan ini

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penempatan_sarpras_items');
        Schema::dropIfExists('penempatan_sarpras');
    }
};
