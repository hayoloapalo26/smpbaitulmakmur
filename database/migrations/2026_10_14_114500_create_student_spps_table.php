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
        Schema::create('student_spps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('tahun_ajaran')->default('2026/2027');
            $table->string('bulan'); // Juli, Agustus, ..., Juni
            $table->string('status')->default('belum_lunas'); // 'lunas', 'belum_lunas'
            $table->decimal('nominal', 12, 2)->nullable();
            $table->date('tanggal_bayar')->nullable();
            $table->string('metode_pembayaran')->nullable()->default('Tunai'); // 'Tunai', 'Transfer Bank', 'Lainnya'
            $table->string('kwitansi_no')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'tahun_ajaran', 'bulan']);
            $table->index(['institution_id', 'classroom_id', 'tahun_ajaran', 'bulan'], 'spps_inst_class_ta_bln_idx');
            $table->index(['tahun_ajaran', 'bulan', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_spps');
    }
};
