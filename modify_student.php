<?php
$file = 'app/Filament/Staff/Resources/StudentResource/Pages/ManageStudents.php';
$content = file_get_contents($file);
$content = str_replace(
    "use Filament\Actions;",
    "use Filament\Actions;\nuse App\Filament\Exports\StudentExporter;\nuse App\Filament\Imports\StudentImporter;",
    $content
);
$content = str_replace(
    "Actions\CreateAction::make(),",
    "Actions\ImportAction::make()->importer(StudentImporter::class)->label('Import Data Siswa')->icon('heroicon-o-arrow-down-tray')->color('success'),\n            Actions\ExportAction::make()->exporter(StudentExporter::class)->label('Export Data Siswa')->icon('heroicon-o-arrow-up-tray')->color('warning'),\n            Actions\CreateAction::make(),",
    $content
);
file_put_contents($file, $content);

$resFile = 'app/Filament/Staff/Resources/StudentResource.php';
$resContent = file_get_contents($resFile);
if (strpos($resContent, 'ExportBulkAction') === false) {
    $resContent = str_replace(
        "use Filament\Tables;",
        "use Filament\Tables;\nuse Filament\Tables\Actions\ExportBulkAction;\nuse App\Filament\Exports\StudentExporter;",
        $resContent
    );
    $resContent = str_replace(
        "Tables\Actions\DeleteBulkAction::make(),",
        "Tables\Actions\DeleteBulkAction::make(),\n                    ExportBulkAction::make()->exporter(StudentExporter::class)->label('Export Terpilih'),",
        $resContent
    );
    file_put_contents($resFile, $resContent);
}
