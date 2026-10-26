<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\SchoolPillar::where('order', 9)->update(['name' => 'Layanan Khusus (Bimbingan Konseling)']);
echo 'SUCCESS: Renamed Pillar 9.';

