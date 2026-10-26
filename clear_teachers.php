<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::whereHas('roles', function($q) {
    $q->whereIn('name', ['guru_mapel', 'wali_kelas', 'guru_alquran', 'guru_ekstrakurikuler', 'bk']);
})->get();

foreach ($users as $u) {
    $u->delete();
}

User::where('email', 'like', '%@smpi.com')->delete();
User::where('email', 'like', 'test%')->delete();

echo "Deleted dummy teachers.\n";
