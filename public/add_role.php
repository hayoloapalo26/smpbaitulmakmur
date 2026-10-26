<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'waka_kesiswaan', 'guard_name' => 'web']);
echo 'SUCCESS: Waka Kesiswaan role created.';

