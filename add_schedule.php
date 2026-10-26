<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$hariMap = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
$hari_ini = $hariMap[now()->dayOfWeek];

$guru = App\Models\User::where('email', 'guru.mtk@gmail.com')->first();
if ($guru) {
    App\Models\Schedule::firstOrCreate([
        'classroom_id' => 1,
        'subject_id' => 2, // MTK
        'teacher_id' => $guru->id,
        'hari' => $hari_ini,
        'jam_ke' => 1
    ]);
    echo "Schedule created for today ($hari_ini) for guru.mtk@gmail.com\n";
}
