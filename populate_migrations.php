<?php
$files = glob("database/migrations/*_create_mandatory_tasks_table.php");
if (!empty($files)) {
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
        Schema::create('mandatory_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('target_roles')->nullable();
            $table->json('form_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandatory_tasks');
    }
};
PHP;
    file_put_contents($file, $content);
}

$files2 = glob("database/migrations/*_create_mandatory_task_submissions_table.php");
if (!empty($files2)) {
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
        Schema::create('mandatory_task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandatory_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('answers')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandatory_task_submissions');
    }
};
PHP;
    file_put_contents($file2, $content2);
}
echo "Migrations populated.\n";
