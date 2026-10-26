<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = \App\Models\Student::where('nis', 'SMP-100999')->first();
if ($student) {
    $siswaUser = \App\Models\User::updateOrCreate(
        ['username' => 'ahmadsiswa'],
        [
            'name' => 'Ahmad Budi Junior (Siswa)',
            'email' => 'ahmad@smpi.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'student_id' => $student->id,
            'institution_id' => 2,
            'is_active' => true,
        ]
    );
    $siswaUser->syncRoles(['siswa']);
    echo 'Siswa account created!';
} else {
    echo 'Student not found!';
}

