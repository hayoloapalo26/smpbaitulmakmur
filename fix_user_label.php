<?php
$file = 'app/Filament/Staff/Resources/UserResource.php';
$content = file_get_contents($file);
$content = str_replace("protected static ?string \$modelLabel = 'Manajemen Staf/Guru';", "protected static ?string \$modelLabel = 'Manajemen Akun (User)';", $content);
$content = str_replace("protected static ?string \$pluralModelLabel = 'Manajemen Staf/Guru';", "protected static ?string \$pluralModelLabel = 'Semua Akun Sistem';", $content);
file_put_contents($file, $content);
echo "UserResource label updated.\n";
