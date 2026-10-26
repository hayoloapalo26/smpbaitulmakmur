<?php
$timestamp = date("Y_m_d_His", strtotime("+5 seconds"));
$migration = "database/migrations/{$timestamp}_add_institution_id_to_classrooms_table.php";

$content = <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('classrooms', function (Blueprint $table) {
            if (!Schema::hasColumn('classrooms', 'institution_id')) {
                $table->foreignId('institution_id')->nullable()->constrained()->cascadeOnDelete();
            }
        });
    }
    public function down(): void {
        Schema::table('classrooms', function (Blueprint $table) {
            if (Schema::hasColumn('classrooms', 'institution_id')) {
                $table->dropForeign(['institution_id']);
                $table->dropColumn('institution_id');
            }
        });
    }
};
PHP;
file_put_contents($migration, $content);
echo "Add institution_id migration generated.\n";
