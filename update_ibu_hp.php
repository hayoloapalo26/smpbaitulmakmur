<?php
$file = 'app/Filament/Staff/Resources/StudentResource/Pages/ManageStudents.php';
$content = file_get_contents($file);
$content = str_replace(
    "'no_hp_ibu' => (!empty(\$row[26]) && strlen(\$row[26]) > 10) ? \$row[26] : null,",
    "'no_hp_ibu' => null, // Sesuai CSV tidak ada no HP ibu",
    $content
);
file_put_contents($file, $content);
echo "Updated.\n";
