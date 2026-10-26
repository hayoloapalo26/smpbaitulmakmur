<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\Institution::updateOrCreate(['id' => 3], ['name' => 'KB/TK Islam Baitul Makmur', 'slug' => 'tk']);
\App\Models\Institution::updateOrCreate(['id' => 4], ['name' => 'SD Islam Baitul Makmur', 'slug' => 'sd']);
\App\Models\Institution::updateOrCreate(['id' => 5], ['name' => 'SMK Baitul Makmur', 'slug' => 'smk']);
\App\Models\Institution::updateOrCreate(['id' => 6], ['name' => 'MA Baitul Makmur', 'slug' => 'ma']);
\App\Models\Institution::updateOrCreate(['id' => 7], ['name' => 'Pondok Pesantren Baitul Makmur', 'slug' => 'ponpes']);

echo 'SUCCESS';

