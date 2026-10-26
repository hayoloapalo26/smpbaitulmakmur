<?php
$file = 'app/Filament/Staff/Resources/StudentResource.php';
$content = file_get_contents($file);

// Add use statements if not exist
if (strpos($content, 'App\Filament\Exports\StudentExporter') === false) {
    $content = str_replace(
        "use App\Models\Student;",
        "use App\Models\Student;\nuse App\Filament\Exports\StudentExporter;\nuse App\Filament\Imports\StudentImporter;\nuse Filament\Tables\Actions\ExportAction;\nuse Filament\Tables\Actions\ImportAction;",
        $content
    );
}

// Add header actions to ListRecords (which is inside StudentResource/Pages usually, but let's check where header actions are. 
// Filament 3 usually puts header actions in Pages/ListStudents.php
