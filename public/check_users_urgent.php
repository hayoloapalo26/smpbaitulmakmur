<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo 'TOTAL USERS: ' . \App\Models\User::count() . '<br>';
foreach(\App\Models\User::all() as $u) {
    echo $u->email . ' | pwd: ' . $u->password . ' | active: ' . $u->is_active . '<br>';
}

