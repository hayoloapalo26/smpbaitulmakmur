<?php
$file = 'app/Providers/AppServiceProvider.php';
$content = file_get_contents($file);

$oldGate = <<<'PHP'
        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });
PHP;

$newGate = <<<'PHP'
        Gate::before(function ($user, $ability) {
            if ($user->hasAnyRole([
                'superadmin', 
                'yayasan', 
                'ketua yayasan', 
                'wakil ketua yayasan', 
                'sekretaris yayasan', 
                'tim pengembang yayasan'
            ])) {
                return true;
            }
            return null;
        });
PHP;

$content = str_replace($oldGate, $newGate, $content);
file_put_contents($file, $content);
echo "AppServiceProvider updated to allow yayasan roles full access.\n";
