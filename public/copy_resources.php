<?php
$source = __DIR__.'/../app/Filament/Yayasan/Resources/LibraryAssessmentResource.php';
$content = file_get_contents($source);

$transport = str_replace(['LibraryAssessment', 'library-assessments', 'Penilaian Perpustakaan', 'heroicon-o-book-open'], ['TransportAssessment', 'transport-assessments', 'Penilaian Transportasi', 'heroicon-o-truck'], $content);
file_put_contents(__DIR__.'/../app/Filament/Yayasan/Resources/TransportAssessmentResource.php', $transport);

$lab = str_replace(['LibraryAssessment', 'library-assessments', 'Penilaian Perpustakaan', 'heroicon-o-book-open'], ['LabAssessment', 'lab-assessments', 'Penilaian Labkom', 'heroicon-o-computer-desktop'], $content);
file_put_contents(__DIR__.'/../app/Filament/Yayasan/Resources/LabAssessmentResource.php', $lab);

$canteen = str_replace(['LibraryAssessment', 'library-assessments', 'Penilaian Perpustakaan', 'heroicon-o-book-open'], ['CanteenAssessment', 'canteen-assessments', 'Penilaian Kantin', 'heroicon-o-building-storefront'], $content);
file_put_contents(__DIR__.'/../app/Filament/Yayasan/Resources/CanteenAssessmentResource.php', $canteen);

$pagesDir = __DIR__.'/../app/Filament/Yayasan/Resources/LibraryAssessmentResource/Pages';
if (is_dir($pagesDir)) {
    @mkdir(__DIR__.'/../app/Filament/Yayasan/Resources/TransportAssessmentResource/Pages', 0755, true);
    foreach (glob($pagesDir . '/*.php') as $file) {
        $name = basename($file);
        $pageContent = file_get_contents($file);
        $pageContent = str_replace(['LibraryAssessment', 'library-assessments'], ['TransportAssessment', 'transport-assessments'], $pageContent);
        file_put_contents(__DIR__.'/../app/Filament/Yayasan/Resources/TransportAssessmentResource/Pages/' . $name, $pageContent);
    }
    
    @mkdir(__DIR__.'/../app/Filament/Yayasan/Resources/LabAssessmentResource/Pages', 0755, true);
    foreach (glob($pagesDir . '/*.php') as $file) {
        $name = basename($file);
        $pageContent = file_get_contents($file);
        $pageContent = str_replace(['LibraryAssessment', 'library-assessments'], ['LabAssessment', 'lab-assessments'], $pageContent);
        file_put_contents(__DIR__.'/../app/Filament/Yayasan/Resources/LabAssessmentResource/Pages/' . $name, $pageContent);
    }
    
    @mkdir(__DIR__.'/../app/Filament/Yayasan/Resources/CanteenAssessmentResource/Pages', 0755, true);
    foreach (glob($pagesDir . '/*.php') as $file) {
        $name = basename($file);
        $pageContent = file_get_contents($file);
        $pageContent = str_replace(['LibraryAssessment', 'library-assessments'], ['CanteenAssessment', 'canteen-assessments'], $pageContent);
        file_put_contents(__DIR__.'/../app/Filament/Yayasan/Resources/CanteenAssessmentResource/Pages/' . $name, $pageContent);
    }
}
echo 'SUCCESS COPY';
