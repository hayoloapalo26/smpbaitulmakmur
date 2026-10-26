<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = \App\Models\User::firstOrCreate(
    ['email' => 'tata_usaha@smpi.com'],
    ['name' => 'Akun Tes Tata Usaha', 'password' => \Illuminate\Support\Facades\Hash::make('password')]
);

$u->name = 'Akun Tes Tata Usaha';
$u->password = \Illuminate\Support\Facades\Hash::make('password');
$u->save();

if (!$u->hasRole('tata_usaha')) {
    $u->assignRole('tata_usaha');
}
echo 'SUCCESS';
