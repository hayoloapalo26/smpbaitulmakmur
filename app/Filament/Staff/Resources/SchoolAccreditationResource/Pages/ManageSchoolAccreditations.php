<?php

namespace App\Filament\Staff\Resources\SchoolAccreditationResource\Pages;

use App\Filament\Staff\Resources\SchoolAccreditationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSchoolAccreditations extends ManageRecords
{
    protected static string $resource = SchoolAccreditationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Akreditasi')
                ->icon('heroicon-o-plus-circle')
                ->modalHeading('Input Data Akreditasi Sekolah')
                ->modalDescription('Masukkan data akreditasi sekolah, nomor SK, masa berlaku, hasil, nama asesor, dan link berkas dokumen drive.')
                ->modalSubmitActionLabel('Simpan Data Akreditasi'),
        ];
    }
}
