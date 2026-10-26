<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$smpPillars = \App\Models\SchoolPillar::where('institution_id', 2)->with('tasks')->get();
$instIds = [3, 4, 5, 6, 7]; // TK, SD, SMK, MA, Ponpes

foreach ($instIds as $id) {
    if (\App\Models\SchoolPillar::where('institution_id', $id)->exists()) continue;
    
    foreach ($smpPillars as $pillar) {
        $newPillar = $pillar->replicate();
        $newPillar->institution_id = $id;
        $newPillar->save();
        
        foreach ($pillar->tasks as $task) {
            $newTask = $task->replicate();
            $newTask->school_pillar_id = $newPillar->id;
            $newTask->save();
        }
    }
}
echo 'SUCCESS cloned pillars';

