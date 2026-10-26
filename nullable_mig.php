<?php
$timestamp = date("Y_m_d_His");
$migration = "database/migrations/{$timestamp}_make_wali_kelas_id_nullable_on_classrooms_table.php";

$content = <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('wali_kelas_id')->nullable()->change();
        });
    }
    public function down(): void {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('wali_kelas_id')->nullable(false)->change();
        });
    }
};
PHP;
file_put_contents($migration, $content);
echo "Nullable migration generated.\n";
