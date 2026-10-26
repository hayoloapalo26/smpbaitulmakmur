<?php
$file = 'app/Filament/Staff/Resources/TodoTaskResource.php';
$content = file_get_contents($file);

$content = str_replace('protected static ?string $navigationGroup = \'Manajemen Tugas\';', 'protected static ?string $navigationGroup = \'Tugas & Kewajiban\';', $content);
$content = str_replace('protected static ?string $navigationLabel = \'Kelola To-Do List\';', 'protected static ?string $navigationLabel = \'Kelola Penugasan (Atasan)\';', $content);
$content = str_replace('protected static ?string $pluralModelLabel = \'To-Do List\';', "protected static ?int \$navigationSort = 3;\n    protected static ?string \$pluralModelLabel = 'To-Do List';", $content);

file_put_contents($file, $content);
echo "TodoTaskResource updated.\n";
