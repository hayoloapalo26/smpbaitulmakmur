<?php
$file = 'app/Policies/UserPolicy.php';
$content = file_get_contents($file);

$content = str_replace(
    "return \$user->hasAnyRole(['kepala_sekolah', 'yayasan']);",
    "return \$user->hasAnyRole(['kepala_sekolah', 'yayasan', 'tata_usaha']);",
    $content
);

file_put_contents($file, $content);
echo "UserPolicy updated.\n";
