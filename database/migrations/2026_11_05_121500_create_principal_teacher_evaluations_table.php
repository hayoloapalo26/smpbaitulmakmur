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
        if (!Schema::hasTable('principal_teacher_evaluations')) {
            Schema::create('principal_teacher_evaluations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('institution_id')->nullable()->index();
                $table->unsignedBigInteger('teacher_id')->index();
                $table->unsignedBigInteger('evaluator_id')->index(); // Kepala Sekolah
                $table->unsignedBigInteger('academic_year_id')->nullable()->index();
                $table->unsignedTinyInteger('bulan')->index(); // 1 - 12
                $table->integer('tahun')->index(); // e.g. 2026
                $table->date('tanggal_penilaian');
                $table->decimal('score', 5, 2); // 0.00 - 100.00
                $table->string('predikat', 50)->nullable(); // Sangat Baik, Baik, Cukup, Kurang
                $table->json('aspek_penilaian')->nullable(); // detail sub-skor aspek penilaian
                $table->text('catatan')->nullable(); // Catatan pembinaan kepala sekolah
                $table->string('status', 30)->default('dinilai'); // dinilai, draft
                $table->timestamps();

                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('evaluator_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('principal_teacher_evaluations');
    }
};
