<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tasks = \App\Models\SchoolPillarTask::whereHas('pillar', function($q) {
    $q->where('order', 9)->where('institution_id', 2);
})->get(['code', 'name', 'assigned_role']);
echo json_encode($tasks);

