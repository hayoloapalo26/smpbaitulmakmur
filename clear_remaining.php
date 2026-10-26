<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::whereNotIn('email', ['admin@gmail.com', 'kepsek@gmail.com'])->get();
foreach ($users as $u) {
    $u->delete();
}
echo "All other dummy users deleted.\n";
