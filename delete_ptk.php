<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::whereDoesntHave('roles', function($q) {
    $q->whereIn('name', ['superadmin', 'siswa', 'orang_tua']);
})->get();

echo "Found " . $users->count() . " PTK to delete.\n";

$deleted = 0;
foreach($users as $u) {
    // Make sure we don't accidentally delete superadmin just in case
    if(!$u->hasRole('superadmin')) {
        $u->delete();
        $deleted++;
    }
}

echo "Deleted $deleted users.\n";
