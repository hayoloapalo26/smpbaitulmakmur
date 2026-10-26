<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
$db = 'Tables_in_' . env('DB_DATABASE', 'smpi'); // Adjust for correct prop
$tableNames = [];
foreach($tables as $t) {
    $prop = array_values((array)$t)[0];
    $tableNames[] = $prop;
}
echo json_encode($tableNames);

