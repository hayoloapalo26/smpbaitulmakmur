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
        Schema::create('learning_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penyusun_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('penelaah_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('mata_pelajaran')->nullable();
            $table->string('unit_kerja_penyusun')->default('SMPI Baitul Makmur');
            $table->string('unit_kerja_penelaah')->default('SMPI Baitul Makmur');
            
            // Kolom JSON untuk menyimpan 15 jawaban form telaah
            $table->json('evaluations')->nullable();
            
            $table->decimal('average_score', 4, 2)->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_plans');
    }
};
