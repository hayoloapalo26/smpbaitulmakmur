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
        // 1. Tabel Anggota / Routing Siswa Ekstrakurikuler
        if (!Schema::hasTable('extracurricular_members')) {
            Schema::create('extracurricular_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('extracurricular_id')->constrained('extracurriculars')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->string('catatan')->nullable();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->timestamps();

                $table->unique(['extracurricular_id', 'student_id'], 'eskul_student_unique');
            });
        }

        // 2. Tabel Jurnal Mengajar Ekstrakurikuler
        if (!Schema::hasTable('extracurricular_journals')) {
            Schema::create('extracurricular_journals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('extracurricular_id')->constrained('extracurriculars')->cascadeOnDelete();
                $table->foreignId('pembina_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('tanggal');
                $table->time('jam_mulai')->nullable();
                $table->time('jam_selesai')->nullable();
                $table->string('lokasi')->nullable();
                $table->string('materi_kegiatan');
                $table->text('deskripsi_kegiatan')->nullable();
                $table->text('catatan_kegiatan')->nullable();
                $table->enum('status_verifikasi', ['menunggu_verifikasi', 'diterima', 'perlu_revisi'])->default('menunggu_verifikasi');
                $table->text('catatan_kesiswaan')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. Tambah relasi extracurricular_journal_id pada extracurricular_attendances
        if (Schema::hasTable('extracurricular_attendances')) {
            Schema::table('extracurricular_attendances', function (Blueprint $table) {
                if (!Schema::hasColumn('extracurricular_attendances', 'extracurricular_journal_id')) {
                    $table->foreignId('extracurricular_journal_id')->nullable()->after('extracurricular_id')->constrained('extracurricular_journals')->cascadeOnDelete();
                }
            });
        }

        // 4. Tabel Nilai Akhir Semester Ekstrakurikuler
        if (!Schema::hasTable('extracurricular_grades')) {
            Schema::create('extracurricular_grades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('extracurricular_id')->constrained('extracurriculars')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('pembina_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->string('semester')->default('Ganjil');
                $table->float('nilai_angka')->nullable();
                $table->string('predikat', 5)->default('A');
                $table->text('deskripsi')->nullable();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
                $table->timestamps();

                $table->unique(['extracurricular_id', 'student_id', 'academic_year_id', 'semester'], 'eskul_grade_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extracurricular_grades');

        if (Schema::hasTable('extracurricular_attendances')) {
            Schema::table('extracurricular_attendances', function (Blueprint $table) {
                if (Schema::hasColumn('extracurricular_attendances', 'extracurricular_journal_id')) {
                    $table->dropForeign(['extracurricular_journal_id']);
                    $table->dropColumn('extracurricular_journal_id');
                }
            });
        }

        Schema::dropIfExists('extracurricular_journals');
        Schema::dropIfExists('extracurricular_members');
    }
};
