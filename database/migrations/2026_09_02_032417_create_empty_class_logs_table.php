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
        Schema::create('empty_class_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duty_teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_absence_report_id')->constrained('teacher_absence_reports')->cascadeOnDelete();
            $table->text('aktivitas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empty_class_logs');
    }
};
