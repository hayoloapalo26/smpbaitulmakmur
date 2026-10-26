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
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('kode_barang')->nullable()->after('id');
            $table->string('kategori')->default('Meubeler & Furnitur')->after('nama_barang');
            $table->string('merk_model')->nullable()->after('kategori');
            $table->string('nomor_seri')->nullable()->after('merk_model');
            $table->date('tanggal_perolehan')->nullable()->after('nomor_seri');
            $table->string('tahun_perolehan')->nullable()->after('tanggal_perolehan');
            $table->string('satuan')->default('Unit')->after('jumlah');
            $table->integer('jumlah_total')->default(1)->after('satuan');
            $table->integer('jumlah_kondisi_baik')->default(1)->after('jumlah_total');
            $table->integer('jumlah_kondisi_rusak_ringan')->default(0)->after('jumlah_kondisi_baik');
            $table->integer('jumlah_kondisi_rusak_berat')->default(0)->after('jumlah_kondisi_rusak_ringan');
            $table->string('ruangan_lokasi')->nullable()->after('kondisi');
            $table->string('penanggung_jawab')->nullable()->after('ruangan_lokasi');
            $table->string('sumber_dana')->default('Yayasan / SPP')->after('penanggung_jawab');
            $table->string('status_kepemilikan')->default('Milik Sendiri')->after('sumber_dana');
            $table->decimal('harga_perolehan', 15, 2)->default(0)->after('status_kepemilikan');
            $table->decimal('total_nilai_aset', 15, 2)->default(0)->after('harga_perolehan');
            $table->text('spesifikasi')->nullable()->after('total_nilai_aset');
            $table->string('foto_barang')->nullable()->after('spesifikasi');
            $table->text('link_drive_dokumen')->nullable()->after('foto_barang');
            $table->text('keterangan')->nullable()->after('link_drive_dokumen');
            $table->string('status_inventaris')->default('Aktif / Digunakan')->after('keterangan');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('status_inventaris');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn([
                'kode_barang',
                'kategori',
                'merk_model',
                'nomor_seri',
                'tanggal_perolehan',
                'tahun_perolehan',
                'satuan',
                'jumlah_total',
                'jumlah_kondisi_baik',
                'jumlah_kondisi_rusak_ringan',
                'jumlah_kondisi_rusak_berat',
                'ruangan_lokasi',
                'penanggung_jawab',
                'sumber_dana',
                'status_kepemilikan',
                'harga_perolehan',
                'total_nilai_aset',
                'spesifikasi',
                'foto_barang',
                'link_drive_dokumen',
                'keterangan',
                'status_inventaris',
                'user_id',
            ]);
        });
    }
};
