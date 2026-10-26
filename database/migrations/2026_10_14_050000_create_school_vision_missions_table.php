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
        Schema::create('school_vision_missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('tahun_ajaran')->default('2026/2027');
            $table->string('periode')->default('2024 - 2029');
            $table->string('slogan')->nullable();

            // 1. Visi
            $table->text('visi');
            $table->text('indikator_visi')->nullable();

            // 2. Misi
            $table->json('misi')->nullable();

            // 3. Tujuan
            $table->json('tujuan')->nullable();
            $table->text('sasaran_strategis')->nullable();

            $table->string('status')->default('Aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_vision_missions');
    }
};
