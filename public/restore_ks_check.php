<?php
$listSup = __DIR__.'/../app/Filament/Staff/Resources/PengajuanSupervisiResource/Pages/ListPengajuanSupervisis.php';
$content = file_get_contents($listSup);
$content = str_replace(
    "return [ Actions\CreateAction::make()->label('Ajukan Supervisi Baru')->modalHeading('Ajukan Supervisi') ];",
    "if (Auth::user()->hasRole('kepala_sekolah')) return [];\n        return [ Actions\CreateAction::make()->label('Ajukan Supervisi Baru')->modalHeading('Ajukan Supervisi') ];",
    $content
);
file_put_contents($listSup, $content);

$listUkg = __DIR__.'/../app/Filament/Staff/Resources/PengajuanUkgResource/Pages/ListPengajuanUkgs.php';
$content = file_get_contents($listUkg);
$content = str_replace(
    "return [ Actions\CreateAction::make()->label('Ajukan UKG Baru')->modalHeading('Ajukan Uji Kompetensi') ];",
    "if (Auth::user()->hasRole('kepala_sekolah')) return [];\n        return [ Actions\CreateAction::make()->label('Ajukan UKG Baru')->modalHeading('Ajukan Uji Kompetensi') ];",
    $content
);
file_put_contents($listUkg, $content);

echo "RESTORED RESTRICTION";
