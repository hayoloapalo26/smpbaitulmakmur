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
        Schema::create('perbaikan_penggantian_sarpras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Petugas / Staf Sarpras penanggung jawab
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete(); // Master Inventaris yang rusak/hilang
            $table->foreignId('penempatan_sarpras_id')->nullable()->constrained('penempatan_sarpras')->nullOnDelete(); // Ruangan asal barang

            // Informasi Kasus & Pelaporan
            $table->string('nomor_pengurusan')->nullable(); // Contoh: PPG/2026/10/0001
            $table->date('tanggal_laporan');
            $table->date('tanggal_selesai')->nullable();
            $table->string('pelapor_nama')->nullable(); // Guru / Karyawan yang menemukan/melaporkan
            $table->string('pelapor_kontak')->nullable(); // No HP / Kontak pelapor
            $table->string('lokasi_kejadian')->nullable(); // Lokasi spesifik saat rusak / hilang
            $table->integer('jumlah_unit')->default(1); // Jumlah fisik yang rusak/hilang
            $table->string('jenis_kasus')->default('Rusak Ringan'); // Rusak Ringan, Rusak Berat, Hilang / Tidak Ditemukan, Aus Akibat Usia Teknis, Keadaan Kahar / Musibah
            $table->text('kronologi_penyebab'); // Deskripsi penyebab kerusakan atau kronologi kehilangan

            // Rencana & Realisasi Tindakan Penyelesaian
            $table->string('rencana_tindakan')->default('Perbaikan Mandiri'); // Perbaikan Mandiri (Teknisi Sekolah), Servis Vendor / Garansi Resmi, Penggantian Komponen / Sparepart, Penggantian Unit Baru (Replacement), Penghapusan Aset / Afkir (Write-off), Klaim Ganti Rugi Pihak Terkait
            $table->decimal('estimasi_biaya', 15, 2)->default(0); // Estimasi biaya perbaikan atau beli baru
            $table->decimal('realisasi_biaya', 15, 2)->default(0); // Biaya aktual setelah selesai pengerjaan
            $table->string('sumber_dana')->default('Anggaran Rutin Sarpras'); // Anggaran Rutin Sarpras, SPP / Komite, Yayasan, Ganti Rugi Penanggung Jawab, Klaim Garansi, Lainnya
            $table->string('pelaksana_teknisi')->nullable(); // Nama teknisi / vendor servis / toko penyedia unit baru
            $table->string('kontak_pelaksana')->nullable(); // No HP teknisi / vendor
            $table->string('status_proses')->default('Dilaporkan'); // Dilaporkan, Menunggu Persetujuan KS, Sedang Diperbaiki, Menunggu Penggantian Unit, Selesai Diperbaiki, Selesai Diganti Unit Baru, Dihapuskan / Afkir
            $table->text('keterangan_penyelesaian')->nullable(); // Catatan hasil akhir penanganan / serial number unit pengganti

            // Dokumentasi Google Drive (Hemat Penyimpanan Hosting)
            $table->text('link_drive_bukti')->nullable(); // Link folder Google Drive foto fisik kerusakan, Berita Acara (BA) Kehilangan, atau kuitansi nota biaya

            // Alur Persetujuan & Disposisi Kepala Sekolah
            $table->string('status_persetujuan')->default('Menunggu Persetujuan'); // Menunggu Persetujuan, Disetujui, Perlu Evaluasi Ulang, Ditolak
            $table->text('catatan_kepsek')->nullable(); // Catatan disposisi / arahan tindak lanjut dari Kepala Sekolah
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); // Kepala Sekolah yang menyetujui
            $table->dateTime('tanggal_persetujuan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perbaikan_penggantian_sarpras');
    }
};
