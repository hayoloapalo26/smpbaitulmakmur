<?php
$listSop = __DIR__.'/../app/Filament/Staff/Resources/SopResource/Pages/ListSops.php';
$content = file_get_contents($listSop);
$content = str_replace('use Filament\Pages\Actions;', 'use Filament\Actions;', $content);
$content = str_replace('protected function getActions(): array', 'protected function getHeaderActions(): array', $content);
file_put_contents($listSop, $content);

$editSop = __DIR__.'/../app/Filament/Staff/Resources/SopResource/Pages/EditSop.php';
$content = file_get_contents($editSop);
$content = str_replace('use Filament\Pages\Actions;', 'use Filament\Actions;', $content);
$content = str_replace('protected function getActions(): array', 'protected function getHeaderActions(): array', $content);
file_put_contents($editSop, $content);

$listSup = __DIR__.'/../app/Filament/Staff/Resources/PengajuanSupervisiResource/Pages/ListPengajuanSupervisis.php';
$content = file_get_contents($listSup);
$content = str_replace('use Filament\Pages\Actions;', 'use Filament\Actions;', $content);
$content = str_replace('protected function getActions(): array', 'protected function getHeaderActions(): array', $content);
file_put_contents($listSup, $content);

$listUkg = __DIR__.'/../app/Filament/Staff/Resources/PengajuanUkgResource/Pages/ListPengajuanUkgs.php';
$content = file_get_contents($listUkg);
$content = str_replace('use Filament\Pages\Actions;', 'use Filament\Actions;', $content);
$content = str_replace('protected function getActions(): array', 'protected function getHeaderActions(): array', $content);
file_put_contents($listUkg, $content);

echo 'SUCCESS FIXED ACTIONS';
