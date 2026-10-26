<?php
$file = __DIR__.'/../resources/views/filament/staff/pages/dashboard.blade.php';
$content = file_get_contents($file);

$content = str_replace(
    '<div class="space-y-8" wire:poll.60s>',
    "@if(count(\$this->pillarsData) > 0 || auth()->user()->hasAnyRole(['kepala_sekolah', 'superadmin', 'yayasan']))\n    <div class=\"space-y-8\" wire:poll.60s>",
    $content
);

$content = str_replace(
    "          </div>\n\n    @if(auth()->user()->hasAnyRole(['guru_mapel', 'guru_alquran', 'wali_kelas']))",
    "          </div>\n    @endif\n\n    @if(auth()->user()->hasAnyRole(['guru_mapel', 'guru_alquran', 'wali_kelas']))",
    $content
);

file_put_contents($file, $content);
echo "SUCCESS WRAPPED MANAGEMENT DASHBOARD";
