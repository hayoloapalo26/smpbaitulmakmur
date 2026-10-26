<?php
$file = 'app/Models/Student.php';
$content = file_get_contents($file);
$content = str_replace(
    "'nisn'", 
    "'nisn', 'no_kk', 'agama', 'jumlah_saudara_kandung', 'pendidikan_ayah', 'penghasilan_ayah', 'no_hp_ayah', 'pendidikan_ibu', 'penghasilan_ibu', 'no_hp_ibu', 'nama_wali', 'no_hp_wali', 'pendidikan_wali', 'pekerjaan_wali', 'penghasilan_wali'", 
    $content
);
file_put_contents($file, $content);
