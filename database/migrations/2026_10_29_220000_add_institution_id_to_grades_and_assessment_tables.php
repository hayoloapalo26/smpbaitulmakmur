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
        $tables = [
            'grades'                  => 'student_id',
            'teacher_subject_classes' => 'classroom_id',
            'quran_achievements'      => 'student_id',
            'reading_quality_scores'  => 'student_id',
            'character_score_logs'    => 'student_id',
            'daily_attendances'       => 'student_id',
            'subject_attendances'     => 'student_id',
            'prayer_attendances'      => 'student_id',
        ];

        foreach ($tables as $table => $sourceCol) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'institution_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('institution_id')->nullable()->after('id')->constrained('institutions')->nullOnDelete();
                });

                // Backfill existing records with their corresponding institution_id
                try {
                    if ($sourceCol === 'student_id') {
                        DB::statement("UPDATE {$table} SET institution_id = (SELECT institution_id FROM students WHERE students.id = {$table}.student_id) WHERE institution_id IS NULL AND student_id IS NOT NULL");
                    } elseif ($sourceCol === 'classroom_id') {
                        DB::statement("UPDATE {$table} SET institution_id = (SELECT institution_id FROM classrooms WHERE classrooms.id = {$table}.classroom_id) WHERE institution_id IS NULL AND classroom_id IS NOT NULL");
                    }
                } catch (\Throwable $e) {
                    // Ignore backfill errors if tables are empty
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'grades',
            'teacher_subject_classes',
            'quran_achievements',
            'reading_quality_scores',
            'character_score_logs',
            'daily_attendances',
            'subject_attendances',
            'prayer_attendances',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'institution_id')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->dropConstrainedForeignId('institution_id');
                });
            }
        }
    }
};
