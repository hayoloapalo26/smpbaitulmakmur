<?php
$file = 'app/Filament/Staff/Resources/StudentResource.php';
$content = file_get_contents($file);
$content = str_replace(
    "Forms\Components\TextInput::make('nik')->searchable()",
    "Forms\Components\TextInput::make('nik')",
    $content
);
file_put_contents($file, $content);
echo "Fixed searchable on nik\n";
