<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pillars = \App\Models\SchoolPillar::where('order', 9)->whereNotNull('institution_id')->get();
foreach($pillars as $p) {
    // Delete non-BK tasks
    \App\Models\SchoolPillarTask::where('school_pillar_id', $p->id)
        ->whereIn('code', ['9.9.2', '9.9.3', '9.9.4', '9.9.5'])
        ->delete();
        
    // Update texts
    $tasks = \App\Models\SchoolPillarTask::where('school_pillar_id', $p->id)->get();
    foreach($tasks as $t) {
        $t->name = str_replace(['Layanan Khusus Sekolah', 'layanan khusus sekolah', 'layanan khusus'], 'Layanan Bimbingan Konseling (BK)', $t->name);
        if ($t->assigned_role == 'BK, TU') {
            $t->assigned_role = 'BK';
        }
        $t->save();
    }
}
echo 'SUCCESS: Updated Pillar 9 tasks for all institutions.';

