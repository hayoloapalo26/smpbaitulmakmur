<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(24);
\Illuminate\Support\Facades\Auth::login($user);
$res = \App\Filament\Staff\Resources\TeacherProfileResource::canAccess();
echo "Lya canAccess TeacherProfileResource: " . ($res ? "TRUE" : "FALSE");
