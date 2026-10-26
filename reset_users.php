<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// Delete all users
User::truncate(); // Or delete() if you want to trigger model events, but truncate is faster. Note: SQLite might have issues with truncate if there are foreign keys without cascade.

// Better to use delete() to be safe with SQLite foreign keys
User::query()->delete();

// Re-create superadmin
$user = User::create([
    'name' => 'Super Admin Lana',
    'username' => 'lana',
    'email' => 'lanabintang54@gmail.com',
    'password' => Hash::make('admin123'),
    'is_active' => true,
]);

// Ensure superadmin role exists
$superAdminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
$user->assignRole($superAdminRole);

echo "All users deleted and superadmin 'lana' created successfully.\n";
