<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

DB::statement('PRAGMA foreign_keys=OFF;');

$tables = [
    'students', 'guardians', 'student_guardian', 'grades', 'subject_attendances',
    'student_cases', 'student_achievements', 'parent_daily_checklists', 'quran_achievements',
    'reading_quality_scores', 'character_score_logs', 'attribute_violations',
    'prayer_attendances', 'extracurricular_attendances', 'student_todo_lists'
];

foreach ($tables as $table) {
    try {
        DB::table($table)->truncate();
    } catch (\Exception $e) {
        // ignore if not exists
    }
}

try {
    User::whereHas('roles', function($q) {
        $q->whereIn('name', ['siswa', 'orang_tua']);
    })->delete();
} catch (\Exception $e) {}

DB::statement('PRAGMA foreign_keys=ON;');

echo "Done\n";
