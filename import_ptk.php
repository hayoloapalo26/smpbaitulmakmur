<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$data = [
    ['Drs. Syamsul Arifin, M.Ed', 'L', '2023-10-01', '8941741643200000', 'Kepala Sekolah', 'syamsul.arifin@smpi.com', ['kepala_sekolah'], 'Riverside D-326 RT 3 RW 5 Balearjosari Blimbing Kota Malang', '0821-4292-2225'],
    ['Lya Ratnawati, M.Pd', 'P', '2011-01-17', '5244766667210063', 'Waka Kesiswaan', 'lya.ratnawati@smpi.com', ['waka_kesiswaan', 'guru_mapel'], 'Asrama Skodam V Brawijaya, Jl. Kesatrian Terusan AA-11 Malang', '0856-4502-0885'],
    ['Elly Anasrul Firdaus, M.Pd', 'L', '2016-11-14', null, 'Koordinator Madin', 'elly.anasrul@smpi.com', ['guru_mapel'], 'JL. Nailun Selatan No 02 RT 15 RW 05, Kromengan, Kab.Malang', '0857-1414-1855'],
    ['Zaenal Musthofa, S.Pd', 'L', '2012-12-17', '7234749650130103', 'Guru', 'zaenal.musthofa@smpi.com', ['guru_alquran'], 'JL. Kapi Sraba X 55 10 A RT 05 RW 16 Mangliawan Kec.Pakis Malang', '0851-0064-5995'],
    ['Choirul Anisa, S.S S.Pd', 'P', '2012-07-04', '9558766667230143', 'Bendahara BOS, WAKA SARPRAS', 'choirul.anisa@smpi.com', ['sarpras', 'guru_mapel'], 'Jl. Singajaya RT 05/ RW 01 Singopadu - Tulangan - Sidoarjo / Jl. Mayjend Panjaitan XIII No. 31 Malang', '0823-3892-0559'],
    ['Chisbulloh, S.Pd', 'L', '2019-09-16', '0849759660131162', 'Guru', 'chisbulloh@smpi.com', ['guru_alquran', 'guru_mapel'], 'Jl.Pattimura XVI/53 RT 04 RW 03 Klojen Malang', '0857-4678-9222'],
    ['M. Romli, M.Ag', 'L', '2016-06-15', '2557764665130250', 'Guru', 'm.romli@smpi.com', ['guru_alquran'], 'Jl. Sawojajar 17B No.58 Kota Malang', '0812-0417-60625'],
    ['Indah Wahyuningtyas, M.Pd', 'P', '2021-06-21', '1635776677230262', 'Waka Humas', 'indah.wahyuningtyas@smpi.com', ['humas', 'bk', 'guru_mapel'], 'Jl. Janti Barat Padepokan II No. 18 Rt/Rw: 01/ 04, Kec. Sukun Kota Malang', '0877-7391-0381'],
    ['Fajar Setiawan, SM', 'L', '2022-02-02', '5454775676130223', 'Staff Tata Usaha', 'fajar.setiawan@smpi.com', ['tata_usaha'], 'Jl.Sawunggaling RT 03 RW 03 Banjarejo Pakis Malang', '0813-5858-3257'],
    ['Mohammad Aldiansyah', 'L', '2025-08-11', null, 'Guru', 'mohammad.aldiansyah@smpi.com', ['guru_mapel'], 'Dukuh Trosono RT 2 RW 1 Desa Sonorejo Kecamatan Jakenan Kabupaten Pati Jawa Tengah', '0821-3705-6471'],
    ['Nadila Choirunnisa\' Intsani, M.Pd', 'P', '2023-07-26', '0663779680230112', 'Waka Kurikulum', 'nadila.choirunnisa@smpi.com', ['waka_kurikulum', 'guru_mapel'], 'Jalan Raya Candi 2 No.123 RT 02 RW 02 Kelurahan Karangbesuki Kecamatan Sukun Kota Malang', '0896-9730-4259'],
    ['Nauvi Iqomatu Khuluq, M.Pd', 'P', null, null, 'Guru', 'nauvi.iqomatu@smpi.com', ['guru_mapel'], null, null],
    ['Abdul Roji, S.Pd', 'L', null, null, 'Guru', 'abdul.roji@smpi.com', ['guru_mapel'], 'Gg. Kampung Baru RT 01/RW 01, Desa Pakisaji, Kec. Pakisaji, Kab. Malang', '0858-1597-6956'],
    ['Septiana Makrifatul Rosida, S.Pd', 'P', '2025-07-14', null, 'Guru', 'septiana.makrifatul@smpi.com', ['guru_mapel'], 'Dusun Karangjambe, Desa Slamet Kecamatan Tumpang Kab Malang', '0812-1775-7975'],
    ['Andariffki Nur Wijaya, S.Pd', 'L', null, null, 'Guru', 'andariffki.nur@smpi.com', ['guru_mapel'], 'Jl. Candi Panggung No.11 Kota Malang', '0821-7966-2566'],
    ['Siti Ismiati Habibatus Salamah, S.Kom', 'P', '2026-07-13', null, 'Guru', 'siti.ismiati@smpi.com', ['guru_mapel'], null, '0822-2369-1522']
];

foreach ($data as $item) {
    $u = User::firstOrCreate(
        ['email' => $item[5]],
        [
            'name' => $item[0],
            'password' => Hash::make('password')
        ]
    );

    $u->name = $item[0];
    $u->gender = $item[1];
    if ($item[2]) $u->tanggal_masuk = $item[2];
    if ($item[3]) $u->nuptk = $item[3];
    $u->jabatan = $item[4];
    $u->alamat = $item[7];
    if ($item[8]) $u->no_hp = str_replace(['+', ' '], '', $item[8]);
    $u->password = Hash::make('password'); // force reset
    $u->save();

    // Assign roles
    foreach ($item[6] as $role) {
        if (!$u->hasRole($role)) {
            $u->assignRole($role);
        }
    }
}

echo "Berhasil import 16 data PTK Asli\n";
