<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `character_score_logs` MODIFY COLUMN `source` VARCHAR(50) NOT NULL DEFAULT 'guru_kelas'");
        } else {
            Schema::table('character_score_logs', function (Blueprint $table) {
                $table->string('source', 50)->default('guru_kelas')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `character_score_logs` MODIFY COLUMN `source` ENUM('guru_kelas', 'guru_piket', 'orang_tua') NOT NULL DEFAULT 'guru_kelas'");
        } else {
            Schema::table('character_score_logs', function (Blueprint $table) {
                $table->enum('source', ['guru_kelas', 'guru_piket', 'orang_tua'])->default('guru_kelas')->change();
            });
        }
    }
};
