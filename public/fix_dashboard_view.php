<?php
$file = __DIR__.'/../resources/views/filament/staff/pages/dashboard.blade.php';
$content = file_get_contents($file);

// Ensure it's not already added
if (!str_contains($content, 'x-filament-widgets::widgets')) {
    $content = str_replace(
        "</x-filament-panels::page>",
        "\n    @if(auth()->user()->hasAnyRole(['guru_mapel', 'guru_alquran', 'wali_kelas']))\n        <div class=\"mt-8\">\n            <x-filament-widgets::widgets\n                :columns=\"\$this->getColumns()\"\n                :data=\"\$this->getWidgetData()\"\n                :widgets=\"\$this->getVisibleWidgets()\"\n            />\n        </div>\n    @endif\n</x-filament-panels::page>",
        $content
    );
    file_put_contents($file, $content);
    echo "SUCCESS ADDED WIDGETS TO VIEW";
} else {
    echo "WIDGETS ALREADY IN VIEW";
}
