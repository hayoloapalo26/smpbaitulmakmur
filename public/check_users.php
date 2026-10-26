<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::all();
foreach($users as $user) {
    echo $user->id . " - " . $user->name . " - Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
}
