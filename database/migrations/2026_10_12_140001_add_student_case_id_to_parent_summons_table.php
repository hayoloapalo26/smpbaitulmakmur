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
        Schema::table('parent_summons', function (Blueprint $table) {
            $table->foreignId('student_case_id')->nullable()->after('student_id')->constrained('student_cases')->nullOnDelete();
            $table->string('nomor_surat')->nullable()->after('id');
            $table->string('tempat_pertemuan')->nullable()->after('tanggal_pertemuan')->default('Ruang BK / Wali Kelas');
            $table->string('surat_file')->nullable()->after('hasil_pertemuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_summons', function (Blueprint $table) {
            $table->dropForeign(['student_case_id']);
            $table->dropColumn(['student_case_id', 'nomor_surat', 'tempat_pertemuan', 'surat_file']);
        });
    }
};
