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
        if (!Schema::hasTable('teacher_competency_tests')) {
            Schema::create('teacher_competency_tests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assessor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('tanggal');
                $table->integer('score')->default(0);
                $table->text('catatan')->nullable();
                $table->string('status')->default('menunggu');
                $table->dateTime('jadwal')->nullable();
                $table->text('catatan_yayasan')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_competency_tests');
    }
};
