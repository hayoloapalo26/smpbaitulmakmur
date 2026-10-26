<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(3);
echo "User ID 3 roles: \n";
foreach($user->roles as $role) {
    echo "- " . $role->name . "\n";
}
