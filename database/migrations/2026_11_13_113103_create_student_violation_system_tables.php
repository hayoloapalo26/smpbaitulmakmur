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
        // 1. Jurnal Catatan Pelanggaran Harian (Diisi Guru Piket)
        Schema::create('student_violation_logs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();
            $table->string('jam', 10)->default('07:00');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->text('bentuk_pelanggaran'); // Bentuk / Deskripsi Pelanggaran
            $table->integer('poin')->default(5); // Poin pelanggaran
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete(); // Nama Guru Pencatat
            $table->text('tindak_lanjut_langsung')->nullable(); // Tindak Lanjut Langsung
            
            // ACC Guru Piket untuk entri ini
            $table->boolean('acc_piket')->default(false);
            $table->foreignId('acc_piket_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acc_piket_at')->nullable();

            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Catatan Evaluasi & Tindak Lanjut Berkala per Siswa (Mingguan, Bulanan, Triwulan, Semesteran)
        Schema::create('student_violation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('periode_type', 20); // mingguan, bulanan, triwulan, semester
            $table->string('periode_key', 30);  // contoh: '2026-W45', '2026-11', '2026-TW1', '2026-SM1'
            $table->year('tahun')->default(2026);
            
            // Inputan khusus berdasarkan perannya:
            $table->text('catatan_wali_kelas')->nullable(); // Catatan Perkembangan (Mingguan) / Catatan Kepribadian Rapor (Semester)
            $table->text('status_sanksi_bk')->nullable();   // Kategori / Status Sanksi (Bulanan - Diisi BK)
            $table->text('tindak_lanjut_bk')->nullable();   // Tindak Lanjut / Agenda Pembinaan (Bulanan - Diisi BK)
            $table->text('status_sanksi_kesiswaan')->nullable(); // Status Peringatan/Sanksi (Triwulan - Diisi Waka Kesiswaan)
            $table->text('tindak_lanjut_kesiswaan')->nullable(); // Tindak Lanjut & Proses Pembinaan (Triwulan - Diisi Waka Kesiswaan)

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // User terakhir mengedit
            $table->timestamps();

            $table->unique(['student_id', 'periode_type', 'periode_key'], 'uniq_student_violation_record');
        });

        // 3. Status ACC Dokumen Rekapitulasi per Kelas & Periode
        Schema::create('student_violation_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('periode_type', 20); // harian, mingguan, bulanan, triwulan, semester
            $table->string('periode_key', 30);  // '2026-11-13', '2026-W45', '2026-11', '2026-TW1', '2026-SM1'
            
            // ACC Guru Piket (Harian)
            $table->boolean('acc_piket')->default(false);
            $table->foreignId('acc_piket_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acc_piket_at')->nullable();

            // ACC Wali Kelas (Mingguan, Bulanan, Triwulan, Semesteran)
            $table->boolean('acc_wali_kelas')->default(false);
            $table->foreignId('acc_wali_kelas_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acc_wali_kelas_at')->nullable();

            // ACC Guru BK (Bulanan, Triwulan, Semesteran)
            $table->boolean('acc_bk')->default(false);
            $table->foreignId('acc_bk_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acc_bk_at')->nullable();

            // ACC Waka Kesiswaan (Triwulan, Semesteran)
            $table->boolean('acc_waka_kesiswaan')->default(false);
            $table->foreignId('acc_waka_kesiswaan_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acc_waka_kesiswaan_at')->nullable();

            // ACC Kepala Sekolah (Semesteran)
            $table->boolean('acc_kepala_sekolah')->default(false);
            $table->foreignId('acc_kepala_sekolah_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acc_kepala_sekolah_at')->nullable();

            $table->timestamps();

            $table->index(['classroom_id', 'periode_type', 'periode_key'], 'idx_violation_approval');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_violation_approvals');
        Schema::dropIfExists('student_violation_records');
        Schema::dropIfExists('student_violation_logs');
    }
};
