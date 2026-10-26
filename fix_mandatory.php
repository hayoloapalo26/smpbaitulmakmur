<?php
$file = "app/Filament/Staff/Resources/MandatoryTaskResource.php";
$content = file_get_contents($file);
$content = str_replace("'view' => Pages\ViewMandatoryTask::route('/{record}'),", "", $content);
file_put_contents($file, $content);
echo "Fixed MandatoryTaskResource.\n";
