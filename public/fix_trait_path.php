<?php
$file = __DIR__.'/../app/Models/SchoolPillar.php';
$content = file_get_contents($file);
$content = str_replace(
    "use App\Traits\BelongsToInstitution;",
    "use App\Models\Traits\BelongsToInstitution;",
    $content
);
file_put_contents($file, $content);
echo "SUCCESS FIXED TRAIT PATH";
