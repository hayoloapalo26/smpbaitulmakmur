<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::with('roles')->get();

echo "Daftar Akun Login:\n";
echo str_pad("Nama", 30) . " | " . str_pad("Email", 30) . " | " . "Role\n";
echo str_repeat("-", 80) . "\n";

foreach ($users as $user) {
    $roles = $user->roles->pluck('name')->implode(', ');
    if (empty($roles)) {
        $roles = 'Tidak ada role (Siswa/Orang Tua)';
    }
    echo str_pad($user->name, 30) . " | " . str_pad($user->email, 30) . " | " . $roles . "\n";
}
