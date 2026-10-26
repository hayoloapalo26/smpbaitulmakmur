<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pillars = \App\Models\SchoolPillar::all();
foreach($pillars as $p) {
    echo $p->order . " - " . $p->name . "\n";
}
