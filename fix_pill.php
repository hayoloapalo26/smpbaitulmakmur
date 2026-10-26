<?php
$files = [
    'app/Providers/Filament/StaffPanelProvider.php',
    'app/Providers/Filament/YayasanPanelProvider.php'
];

foreach($files as $file) {
    if(file_exists($file)) {
        $content = file_get_contents($file);
        $content = str_replace('border-radius: 20px !important; /* Rounded aesthetic */', 'border-radius: 9999px !important; /* Perfect pill shape */', $content);
        file_put_contents($file, $content);
    }
}
echo "Border radius updated.\n";
