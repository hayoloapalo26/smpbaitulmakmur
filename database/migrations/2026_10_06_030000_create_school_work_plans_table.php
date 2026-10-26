<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_work_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->enum('document_type', ['RKT', 'RKS']);
            $table->string('drive_link');
            $table->enum('status', ['draft', 'submitted', 'reviewing_ketua', 'revision_ketua', 'approved_ketua', 'revision_pembina', 'approved_final'])->default('draft');
            $table->text('ketua_note')->nullable();
            $table->text('pembina_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_work_plans');
    }
};
