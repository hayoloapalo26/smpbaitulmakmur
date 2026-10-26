<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

$roles = ['sarpras', 'humas'];
foreach ($roles as $r) {
    Role::firstOrCreate(['name' => $r]);
}

$sarpras = User::firstOrCreate(
    ['email' => 'sarpras@gmail.com'], 
    [
        'name' => 'Wahyudi, S.T', 
        'password' => Hash::make('password'), 
        'no_induk_yayasan' => 'YBM-2002', 
        'nuptk' => '12345678998', 
        'gender' => 'L', 
        'jabatan' => 'Koordinator Sarpras', 
        'status_kepegawaian' => 'GTY', 
        'no_hp' => '08129999998', 
        'tempat_lahir' => 'Malang', 
        'tanggal_lahir' => '1980-01-01', 
        'jenjang_pendidikan' => 'S1', 
        'universitas' => 'Universitas Brawijaya', 
        'tanggal_masuk' => '2010-01-01', 
        'alamat' => 'Perumahan Guru No. 98'
    ]
);
$sarpras->assignRole('sarpras');

$humas = User::firstOrCreate(
    ['email' => 'humas@gmail.com'], 
    [
        'name' => 'Kurniawan, S.I.Kom', 
        'password' => Hash::make('password'), 
        'no_induk_yayasan' => 'YBM-2003', 
        'nuptk' => '12345678997', 
        'gender' => 'L', 
        'jabatan' => 'Koordinator Humas', 
        'status_kepegawaian' => 'GTY', 
        'no_hp' => '08129999997', 
        'tempat_lahir' => 'Malang', 
        'tanggal_lahir' => '1982-01-01', 
        'jenjang_pendidikan' => 'S1', 
        'universitas' => 'Universitas Muhammadiyah Malang', 
        'tanggal_masuk' => '2012-01-01', 
        'alamat' => 'Perumahan Guru No. 97'
    ]
);
$humas->assignRole('humas');

echo "Users created!\n";
