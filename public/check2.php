<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'Total Pillars: ' . \App\Models\SchoolPillar::count() . ' | NULL: ' . \App\Models\SchoolPillar::whereNull('institution_id')->count();

