<?php
namespace App\Filament\Staff\Resources\PengajuanUkgResource\Pages;
use App\Filament\Staff\Resources\PengajuanUkgResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListPengajuanUkgs extends ListRecords {
    protected static string $resource = PengajuanUkgResource::class;
    protected function getHeaderActions(): array {
        if (Auth::user()->hasRole('kepala_sekolah')) return [];
        return [ Actions\CreateAction::make()->label('Ajukan PKG Baru')->modalHeading('Ajukan Penilaian Kinerja Guru (PKG)') ];
    }
}