<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;
use App\Models\User;
use App\Models\Guardian;
use Illuminate\Support\Facades\DB;

DB::statement('PRAGMA foreign_keys=OFF;');

// Hapus User dengan role siswa dan orang_tua
User::whereHas('roles', function($q) {
    $q->whereIn('name', ['siswa', 'orang_tua']);
})->delete();

// Hapus semua siswa
DB::table('students')->truncate();
DB::table('guardians')->truncate();
DB::table('student_guardian')->truncate();
DB::table('grades')->truncate();
DB::table('subject_attendances')->truncate();
DB::table('student_cases')->truncate();
DB::table('student_achievements')->truncate();
DB::table('parent_daily_checklists')->truncate();
DB::table('quran_achievements')->truncate();
DB::table('reading_quality_scores')->truncate();
DB::table('character_score_logs')->truncate();
DB::table('attribute_violations')->truncate();
DB::table('prayer_attendances')->truncate();
DB::table('extracurricular_attendances')->truncate();
DB::table('student_todo_lists')->truncate();

DB::statement('PRAGMA foreign_keys=ON;');

echo "Dummy data siswa berhasil dihapus!\n";
