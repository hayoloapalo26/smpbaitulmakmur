<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = \App\Models\User::where('email', 'guru_mapel@smpi.com')->first();
echo 'Roles for guru_mapel@smpi.com: ' . $u->roles->pluck('name')->implode(', ');

