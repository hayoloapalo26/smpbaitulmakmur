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
        Schema::create('parent_summons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete(); // wali kelas atau kesiswaan
            $table->date('tanggal_pemanggilan');
            $table->date('tanggal_pertemuan')->nullable();
            $table->text('kasus');
            $table->text('hasil_pertemuan')->nullable();
            $table->enum('status', ['menunggu', 'hadir', 'tidak_hadir'])->default('menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_summons');
    }
};
