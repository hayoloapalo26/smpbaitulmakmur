<?php
require __DIR__.'/vendor/autoload.php';

use League\Csv\Reader;

$csvContent = "NOMOR,KELAS,Nama,NIPD/NIS,JK,NISN,Tempat Lahir,Tanggal Lahir,NIK,Agama,Alamat,RT,RW,Kelurahan,Kecamatan,Kode Pos,Data Ayah,,,,NOMOR HP,Data Ibu,,,,,,Sekolah Asal,No KK
,,,,,,,,,,,,,,,,Nama,Jenjang Pendidikan,Pekerjaan,Penghasilan,,NIK,Nama,Jenjang Pendidikan,Pekerjaan,Penghasilan,NIK,,
1,8,Abdul Hakim Ar Rasyid,0262,L,0116459980";

file_put_contents('test.csv', $csvContent);

try {
    $reader = Reader::createFromPath('test.csv', 'r');
    $reader->setHeaderOffset(0);
    $records = $reader->getRecords();
    foreach ($records as $offset => $record) {
        print_r($record);
        break;
    }
    echo "Success!\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
