<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Institution;
use App\Models\SchoolPillar;

$institutions = [
    ['name' => 'KB/TK', 'slug' => 'kb-tk'],
    ['name' => 'SD', 'slug' => 'sd'],
    ['name' => 'SMP', 'slug' => 'smp'],
    ['name' => 'SMK', 'slug' => 'smk'],
    ['name' => 'MA', 'slug' => 'ma'],
    ['name' => 'Pondok Pesantren', 'slug' => 'ponpes'],
];

foreach ($institutions as $inst) {
    Institution::firstOrCreate(['slug' => $inst['slug']], $inst);
}

$smp = Institution::where('slug', 'smp')->first();
if ($smp) {
    SchoolPillar::whereNull('institution_id')->update(['institution_id' => $smp->id]);
}

echo "Database seeded with institutions and updated existing pillars to SMP.\n";
