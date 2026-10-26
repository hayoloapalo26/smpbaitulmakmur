<?php
$file = 'app/Filament/Staff/Resources/MandatoryTaskResource.php';
$content = file_get_contents($file);
$content = preg_replace('/Forms\\\\Components\\\\Hidden::make\(\'institution_id\'\).*?->required\(\),/s', '', $content);
file_put_contents($file, $content);
echo "Hidden field removed.\n";
