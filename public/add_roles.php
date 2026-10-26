<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$missingRoles = ['wali_kelas', 'guru_alquran', 'bk', 'tata_usaha', 'sarpras', 'humas'];
foreach ($missingRoles as $r) {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
}
echo 'SUCCESS: Created missing roles.';

