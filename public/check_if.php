<?php
$file = __DIR__.'/../resources/views/filament/staff/pages/dashboard.blade.php';
$content = file_get_contents($file);
$if_count = substr_count($content, '@if');
$endif_count = substr_count($content, '@endif');
echo "IF: $if_count, ENDIF: $endif_count";
