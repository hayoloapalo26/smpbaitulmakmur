<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

$role = Role::firstOrCreate(['name' => 'guru_ekstrakurikuler']);
$u = User::firstOrCreate(
    ['email' => 'ekskul@gmail.com'], 
    [
        'name' => 'Agus Pramono, S.Sn', 
        'password' => Hash::make('password'), 
        'no_induk_yayasan' => 'YBM-2001', 
        'nuptk' => '12345678999', 
        'gender' => 'L', 
        'jabatan' => 'Pembina Ekstrakurikuler', 
        'status_kepegawaian' => 'GTT', 
        'no_hp' => '08129999999', 
        'tempat_lahir' => 'Malang', 
        'tanggal_lahir' => '1985-01-01', 
        'jenjang_pendidikan' => 'S1', 
        'universitas' => 'Universitas Negeri Malang', 
        'tanggal_masuk' => '2010-01-01', 
        'alamat' => 'Perumahan Guru No. 99'
    ]
);
$u->assignRole($role);
echo "User ekskul created!\n";
