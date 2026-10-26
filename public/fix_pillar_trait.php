<?php
$file = __DIR__.'/../app/Models/SchoolPillar.php';
$content = file_get_contents($file);
$content = str_replace(
    "use Illuminate\Database\Eloquent\Model;",
    "use Illuminate\Database\Eloquent\Model;\nuse App\Traits\BelongsToInstitution;",
    $content
);
$content = str_replace(
    "protected \$guarded = ['id'];",
    "use BelongsToInstitution;\n\n    protected \$guarded = ['id'];",
    $content
);
file_put_contents($file, $content);
echo "SUCCESS ADDED TRAIT";
