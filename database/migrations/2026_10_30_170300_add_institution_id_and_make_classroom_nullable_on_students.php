<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students', 'institution_id')) {
                    $table->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
                }
                $table->foreignId('classroom_id')->nullable()->change();
                $table->date('tanggal_lahir')->nullable()->change();
            });

            // Backfill institution_id from classrooms if available
            try {
                DB::statement("UPDATE students SET institution_id = (SELECT institution_id FROM classrooms WHERE classrooms.id = students.classroom_id) WHERE institution_id IS NULL AND classroom_id IS NOT NULL");
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'institution_id')) {
                    $table->dropForeign(['institution_id']);
                    $table->dropColumn('institution_id');
                }
            });
        }
    }
};
