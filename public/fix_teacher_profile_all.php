<?php
$file = __DIR__.'/../app/Policies/TeacherProfilePolicy.php';
$content = file_get_contents($file);
$content = str_replace(
    "'bk'",
    "'bk', 'superadmin', 'guru_mapel', 'guru_alquran', 'wali_kelas'",
    $content
);
file_put_contents($file, $content);

$resFile = __DIR__.'/../app/Filament/Staff/Resources/TeacherProfileResource.php';
$resContent = file_get_contents($resFile);
$resContent = str_replace(
    "['tata_usaha', 'superadmin', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'humas', 'sarpras', 'bk']",
    "['tata_usaha', 'superadmin', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'humas', 'sarpras', 'bk', 'guru_mapel', 'guru_alquran', 'wali_kelas']",
    $resContent
);
file_put_contents($resFile, $resContent);

echo "SUCCESS UPDATED ALL ACCESS ROLES";
