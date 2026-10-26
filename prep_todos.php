<?php
$files = glob('database/migrations/*_create_todo_tasks_table.php');
$file = $files[0];
$content = <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('todo_tasks', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['wajib', 'opsional'])->default('opsional');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->json('target_roles')->nullable();
            $table->json('target_users')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_tasks');
    }
};
PHP;
file_put_contents($file, $content);

$files2 = glob('database/migrations/*_create_todo_completions_table.php');
$file2 = $files2[0];
$content2 = <<<'PHP'
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
PHP;
file_put_contents($file2, $content2);
echo "Migrations populated.\n";
