<?php
$file = 'app/Models/User.php';
$content = file_get_contents($file);
if (strpos($content, "'satminkal'") === false) {
    $content = str_replace(
        "'alamat',", 
        "'alamat', 'satminkal',", 
        $content
    );
    file_put_contents($file, $content);
}
