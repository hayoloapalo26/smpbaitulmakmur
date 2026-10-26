<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('yayasan_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('task_type', ['mandatory', 'optional'])->default('mandatory');
            $table->json('target_roles');
            $table->json('form_schema');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('yayasan_task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('yayasan_task_id')->constrained('yayasan_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('answers');
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yayasan_task_submissions');
        Schema::dropIfExists('yayasan_tasks');
    }
};