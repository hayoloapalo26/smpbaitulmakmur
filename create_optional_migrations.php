<?php
$timestamp1 = date("Y_m_d_His");
$timestamp2 = date("Y_m_d_His", strtotime("+1 second"));

$migration1 = "database/migrations/{$timestamp1}_create_optional_tasks_table.php";
$migration2 = "database/migrations/{$timestamp2}_create_optional_task_submissions_table.php";

$content1 = <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('optional_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('target_roles')->nullable();
            $table->json('form_schema')->nullable();
            $table->integer('reward_points')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('optional_tasks');
    }
};
PHP;

$content2 = <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('optional_task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('optional_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('answers')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('optional_task_submissions');
    }
};
PHP;

file_put_contents($migration1, $content1);
file_put_contents($migration2, $content2);
echo "Migrations generated.\n";
