<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\Artisan::call('make:filament-resource', ['name' => 'TransportAssessment', '--panel' => 'yayasan', '--generate' => true]);
\Illuminate\Support\Facades\Artisan::call('make:filament-resource', ['name' => 'LabAssessment', '--panel' => 'yayasan', '--generate' => true]);
\Illuminate\Support\Facades\Artisan::call('make:filament-resource', ['name' => 'CanteenAssessment', '--panel' => 'yayasan', '--generate' => true]);
echo 'SUCCESS';

