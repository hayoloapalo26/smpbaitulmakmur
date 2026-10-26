<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('email', 'yayasan@smp.com')->first();
if ($user) {
    $user->email = 'yayasan@gmail.com';
    $user->save();
    echo "Email updated successfully.\n";
} else {
    // If somehow not found, create it
    $u = User::firstOrCreate(
        ['email' => 'yayasan@gmail.com'],
        ['name' => 'Admin Yayasan', 'password' => bcrypt('password')]
    );
    $u->assignRole('yayasan');
    echo "Yayasan User created with yayasan@gmail.com\n";
}
