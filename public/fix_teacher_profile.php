<?php
$file = __DIR__.'/../app/Filament/Staff/Resources/TeacherProfileResource.php';
$content = file_get_contents($file);
$content = str_replace(
    "return auth()->user()->hasAnyRole(['tata_usaha', 'superadmin', 'kepala_sekolah']);",
    "return auth()->user()->hasAnyRole(['tata_usaha', 'superadmin', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 'humas', 'sarpras', 'bk']);",
    $content
);
file_put_contents($file, $content);
echo "SUCCESS UPDATED RESOURCE CANACCESS";
