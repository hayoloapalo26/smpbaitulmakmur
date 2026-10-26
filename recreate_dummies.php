<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Classroom;
use App\Models\Student;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

$password = Hash::make('password');

$accounts = [
    ['email' => 'wakakur@gmail.com', 'name' => 'Waka Kurikulum', 'role' => 'waka_kurikulum'],
    ['email' => 'wakakes@gmail.com', 'name' => 'Waka Kesiswaan', 'role' => 'waka_kesiswaan'],
    ['email' => 'tu@gmail.com', 'name' => 'Tata Usaha', 'role' => 'tata_usaha'],
    ['email' => 'sarpras@gmail.com', 'name' => 'Sarana Prasarana', 'role' => 'sarpras'],
    ['email' => 'humas@gmail.com', 'name' => 'Humas', 'role' => 'humas'],
    ['email' => 'bk@gmail.com', 'name' => 'Guru BK', 'role' => 'bk'],
    ['email' => 'guru@gmail.com', 'name' => 'Guru Mapel Umum', 'role' => 'guru_mapel'],
    ['email' => 'wali@gmail.com', 'name' => 'Wali Kelas 8', 'role' => 'wali_kelas'],
    ['email' => 'siswa@gmail.com', 'name' => 'Siswa Testing', 'role' => 'siswa'],
    ['email' => 'ortu@gmail.com', 'name' => 'Orang Tua Testing', 'role' => 'orang_tua'],
];

foreach ($accounts as $acc) {
    $user = User::firstOrCreate(
        ['email' => $acc['email']],
        ['name' => $acc['name'], 'password' => $password]
    );
    if (!$user->hasRole($acc['role'])) {
        $user->assignRole($acc['role']);
    }
}

// Setup basic relations for testing
$wali = User::where('email', 'wali@gmail.com')->first();
$kelas = Classroom::firstOrCreate(
    ['tingkat' => '8'],
    ['name' => 'Kelas 8A', 'wali_kelas_id' => $wali->id]
);

$siswa = Student::firstOrCreate(
    ['nis' => '9999'],
    [
        'name' => 'Siswa Testing',
        'classroom_id' => $kelas->id,
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '2010-01-01',
        'character_score' => 80
    ]
);

$userSiswa = User::where('email', 'siswa@gmail.com')->first();
$userSiswa->student_id = $siswa->id;
$userSiswa->save();

echo "Dummy accounts created successfully.\n";
