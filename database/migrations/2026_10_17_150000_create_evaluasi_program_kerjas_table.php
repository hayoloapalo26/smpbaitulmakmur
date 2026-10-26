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
        Schema::create('evaluasi_program_kerjas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Referensi program kerja asal yang sudah di-ACC
            $table->string('program_source_type')->nullable(); // Class model asal (HarmonisasiInternal, HubunganWaliMurid, HubunganInstitusi, HubunganMasyarakat, ProgramHumas)
            $table->unsignedBigInteger('program_source_id')->nullable(); // ID record asal
            $table->string('nama_program'); // Nama program kerja yang dievaluasi
            $table->string('kategori_program'); // Kategori / divisi program (misal: Harmonisasi Internal, Hubungan Wali Murid, dll)
            $table->date('tanggal_pelaksanaan')->nullable(); // Tanggal pelaksanaan kegiatan dari data asal
            $table->string('target_peserta')->nullable(); // Target peserta dari data asal
            $table->string('lokasi_kegiatan')->nullable(); // Lokasi kegiatan dari data asal
            
            // Inputan evaluasi pelaksanaan
            $table->date('tanggal_evaluasi')->default(now()->toDateString()); // Tanggal dilakukan evaluasi
            $table->string('status_keterlaksanaan')->default('Terlaksana Sepenuhnya'); // Terlaksana Sepenuhnya, Terlaksana Sebagian, Tidak Terlaksana
            $table->integer('persentase_ketercapaian')->default(100); // 0 - 100%
            $table->string('predikat')->default('Sangat Baik'); // Sangat Baik, Baik, Cukup, Kurang
            $table->text('realisasi_kegiatan'); // Laporan realisasi & ketercapaian secara faktual
            $table->text('kendala')->nullable(); // Kendala dan hambatan selama pelaksanaan
            $table->text('solusi_tindak_lanjut')->nullable(); // Solusi dan rekomendasi untuk tindak lanjut
            $table->text('link_drive_lpj')->nullable(); // Tautan Google Drive dokumen LPJ / foto / angket evaluasi
            
            // Catatan dan review Kepala Sekolah
            $table->text('catatan_kepsek')->nullable(); // Arahan atau tanggapan Kepala Sekolah terhadap evaluasi ini
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); // Kepala Sekolah yang meninjau
            $table->dateTime('tanggal_review')->nullable();
            
            $table->string('status')->default('Final'); // Draft, Final, Ditinjau
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluasi_program_kerjas');
    }
};
