<?php

$dir = __DIR__.'/app/Policies';
$files = scandir($dir);

foreach ($files as $file) {
    if (strpos($file, '.php') === false) continue;
    
    $path = $dir.'/'.$file;
    $content = file_get_contents($path);
    
    $modified = false;
    
    // Patch viewAny
    $patternViewAny = "/(public function viewAny\([a-zA-Z0-9_]*\s*\\\$user\): bool\s*\{)(.*?)(^\s*\})/sm";
    if (preg_match($patternViewAny, $content, $matches)) {
        $body = $matches[2];
        if (strpos($body, "if (\$user->hasRole('kepala_sekolah')) return true;") === false) {
            $newBody = "\n        if (\$user->hasRole('kepala_sekolah')) return true;" . $body;
            $content = str_replace($matches[0], $matches[1] . $newBody . $matches[3], $content);
            $modified = true;
        }
    }
    
    // Patch view
    $patternView = "/(public function view\([a-zA-Z0-9_]*\s*\\\$user, [a-zA-Z0-9_]+ \\\$model\): bool\s*\{)(.*?)(^\s*\})/sm";
    if (preg_match($patternView, $content, $matches)) {
        $body = $matches[2];
        if (strpos($body, "if (\$user->hasRole('kepala_sekolah')) return true;") === false) {
            $newBody = "\n        if (\$user->hasRole('kepala_sekolah')) return true;" . $body;
            $content = str_replace($matches[0], $matches[1] . $newBody . $matches[3], $content);
            $modified = true;
        }
    }
    
    if ($modified) {
        file_put_contents($path, $content);
        echo "Patched $file\n";
    }
}
