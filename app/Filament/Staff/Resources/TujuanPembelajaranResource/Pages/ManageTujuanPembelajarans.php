<?php

namespace App\Filament\Staff\Resources\TujuanPembelajaranResource\Pages;

use App\Filament\Staff\Resources\TujuanPembelajaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTujuanPembelajarans extends ManageRecords
{
    protected static string $resource = TujuanPembelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Tujuan Pembelajaran (TP)')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
