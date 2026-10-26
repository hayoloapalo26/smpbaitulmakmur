<?php
$file = 'app/Filament/Staff/Resources/StudentResource/Pages/ManageStudents.php';
$content = file_get_contents($file);

$content = str_replace(
    "['nis' => \$row[3] ?? null],",
    "['nis' => !empty(\$row[3]) ? trim(\$row[3]) : 'NIS-' . rand(1000, 9999)],",
    $content
);

file_put_contents($file, $content);
echo "Added nis fallback.\n";
