<?php
$file = 'app/Filament/Staff/Resources/TeacherProfileResource/Pages/ListTeacherProfiles.php';
$content = file_get_contents($file);
$content = str_replace(
    "use Filament\Actions;",
    "use Filament\Actions;\nuse App\Filament\Exports\UserExporter;\nuse App\Filament\Imports\UserImporter;",
    $content
);
$content = str_replace(
    "Actions\CreateAction::make(),",
    "Actions\ImportAction::make()->importer(UserImporter::class)->label('Import Data PTK')->icon('heroicon-o-arrow-down-tray')->color('success'),\n            Actions\ExportAction::make()->exporter(UserExporter::class)->label('Export Data PTK')->icon('heroicon-o-arrow-up-tray')->color('warning'),\n            Actions\CreateAction::make(),",
    $content
);
file_put_contents($file, $content);

$resFile = 'app/Filament/Staff/Resources/TeacherProfileResource.php';
$resContent = file_get_contents($resFile);
if (strpos($resContent, 'ExportBulkAction') === false) {
    $resContent = str_replace(
        "use Filament\Tables;",
        "use Filament\Tables;\nuse Filament\Tables\Actions\ExportBulkAction;\nuse App\Filament\Exports\UserExporter;",
        $resContent
    );
    $resContent = str_replace(
        "Tables\Actions\DeleteBulkAction::make(),",
        "Tables\Actions\DeleteBulkAction::make(),\n                    ExportBulkAction::make()->exporter(UserExporter::class)->label('Export Terpilih'),",
        $resContent
    );
    file_put_contents($resFile, $resContent);
}

echo "Done\n";
