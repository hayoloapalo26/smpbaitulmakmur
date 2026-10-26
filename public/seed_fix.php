<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'DashboardFixSeeder', '--force' => true]);
    echo 'SUCCESS: ' . \Illuminate\Support\Facades\Artisan::output();
} catch (\Exception $e) {
    echo 'ERROR: ' . $e->getMessage();
}

