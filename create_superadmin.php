<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$role = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

$user = User::firstOrCreate(
    ['email' => 'superadmin@smpi-bbm.test'],
    [
        'name' => 'Super Administrator',
        'password' => Hash::make('password')
    ]
);

if (!$user->hasRole('superadmin')) {
    $user->assignRole('superadmin');
}

echo "Superadmin created.\n";
