<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_task_id')->constrained('todo_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_done')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->text('note')->nullable();
            $table->string('proof_file')->nullable();
            $table->timestamps();
            
            $table->unique(['todo_task_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_completions');
    }
};