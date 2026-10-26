<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::find(99);
if ($user && $user->avatar_url) {
    Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_url);
    $user->avatar_url = null;
    $user->save();
    echo "Avatar deleted.\n";
} else {
    echo "Avatar already empty or user not found.\n";
}
