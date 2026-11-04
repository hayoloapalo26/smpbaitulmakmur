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
        if (!Schema::hasTable('rapor_grades')) {
            Schema::create('rapor_grades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
                $table->string('semester')->default('Ganjil');

                // 1. Nilai Asli (Murni dari Guru Mapel)
                $table->float('nilai_pengetahuan_asli')->nullable();
                $table->float('nilai_keterampilan_asli')->nullable();
                $table->float('nilai_akhir_asli')->nullable();
                $table->string('predikat_pengetahuan_asli', 5)->nullable();
                $table->string('predikat_keterampilan_asli', 5)->nullable();

                // 2. Nilai Olahan / Nilai Bantuan / Nilai Rapot yang Diedit
                $table->float('nilai_pengetahuan_olah')->nullable();
                $table->float('nilai_keterampilan_olah')->nullable();
                $table->float('nilai_akhir_olah')->nullable();
                $table->string('predikat_pengetahuan_olah', 5)->nullable();
                $table->string('predikat_keterampilan_olah', 5)->nullable();

                // 3. Deskripsi Capaian Kompetensi Rapor
                $table->text('deskripsi_pengetahuan')->nullable();
                $table->text('deskripsi_keterampilan')->nullable();

                // 4. Status & Keterangan Bantuan
                $table->boolean('is_custom')->default(false);
                $table->string('catatan_penyesuaian')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();

                $table->timestamps();

                $table->unique(['student_id', 'subject_id', 'academic_year_id', 'semester'], 'rapor_grade_student_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rapor_grades');
    }
};
