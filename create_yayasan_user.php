<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

Role::firstOrCreate(['name' => 'yayasan']);
$u = User::firstOrCreate(
    ['email' => 'yayasan@smp.com'],
    ['name' => 'Admin Yayasan', 'password' => bcrypt('password')]
);
$u->assignRole('yayasan');
echo "Yayasan User created: yayasan@smp.com / password\n";
