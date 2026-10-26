<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::statement('PRAGMA foreign_keys=OFF;');

$tables = [
    'teacher_attendances', 'teacher_leave_requests', 'teaching_journals', 
    'teaching_materials', 'teacher_supervisions', 'teacher_absence_reports', 
    'duty_schedules', 'duty_swap_requests', 'empty_class_logs'
];

foreach ($tables as $table) {
    try {
        DB::table($table)->truncate();
    } catch (\Exception $e) {}
}

DB::statement('PRAGMA foreign_keys=ON;');

echo "Truncated teacher related tables.\n";
