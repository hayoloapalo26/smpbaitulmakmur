<?php

namespace App\Filament\Staff\Resources\CapaianPembelajaranResource\Pages;

use App\Filament\Staff\Resources\CapaianPembelajaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCapaianPembelajarans extends ManageRecords
{
    protected static string $resource = CapaianPembelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Capaian Pembelajaran (CP)')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
