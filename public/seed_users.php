<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'InstitutionUserSeeder', '--force' => true]);
echo 'SUCCESS: Seeded missing users.';

