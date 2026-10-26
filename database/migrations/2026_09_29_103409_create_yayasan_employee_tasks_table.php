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
        Schema::create('yayasan_employee_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yayasan_employee_id')->constrained()->cascadeOnDelete();
            $table->string('task_name');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yayasan_employee_tasks');
    }
};
