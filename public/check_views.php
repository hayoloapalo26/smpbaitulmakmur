<?php
$output = shell_exec('D:\php\php.exe artisan view:cache 2>&1');
echo $output ?: "DONE";
