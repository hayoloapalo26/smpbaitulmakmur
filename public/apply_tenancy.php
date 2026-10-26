<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

$tables = [
    'alumni_tracers', 'attribute_violations', 'bk_reports', 'bk_work_programs', 'character_score_logs', 
    'classrooms', 'daily_attendances', 'dcm_responses', 'document_archives', 'duty_schedules', 
    'duty_swap_requests', 'empty_class_logs', 'extracurricular_attendances', 'extracurriculars', 
    'grades', 'humas_programs', 'inventories', 'learning_plans', 'mandatory_tasks', 'meeting_minutes', 
    'optional_tasks', 'osis_programs', 'parent_summons', 'prayer_attendances', 'program_kepseks', 
    'program_kesiswaans', 'program_kurikulums', 'program_sarpras', 'quran_achievements', 
    'reading_quality_scores', 'rppms', 'schedules', 'school_work_plans', 'student_achievements', 
    'student_cases', 'student_halangans', 'student_leave_requests', 'students', 'subject_attendances', 
    'subjects', 'teacher_absence_reports', 'teacher_attendances', 'teacher_leave_requests', 
    'teacher_profiles', 'teacher_subject_classes', 'teacher_supervisions', 'teacher_todo_lists', 
    'teaching_journals', 'teaching_materials', 'todo_tasks', 'work_programs', 'yayasan_programs'
];

foreach ($tables as $table) {
    if (Schema::hasTable($table) && !Schema::hasColumn($table, 'institution_id')) {
        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            $blueprint->foreignId('institution_id')->nullable()->constrained('institutions')->cascadeOnDelete();
        });
        
        // Update existing data to belong to SMP (ID = 2)
        \Illuminate\Support\Facades\DB::table($table)->update(['institution_id' => 2]);
    }
}
echo 'SUCCESS: Applied institution_id to all tables.';

