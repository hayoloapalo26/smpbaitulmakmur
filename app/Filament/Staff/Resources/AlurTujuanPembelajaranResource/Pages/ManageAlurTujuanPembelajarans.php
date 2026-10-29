<?php

namespace App\Filament\Staff\Resources\AlurTujuanPembelajaranResource\Pages;

use App\Filament\Staff\Resources\AlurTujuanPembelajaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAlurTujuanPembelajarans extends ManageRecords
{
    protected static string $resource = AlurTujuanPembelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Alur Tujuan Pembelajaran (ATP)')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
