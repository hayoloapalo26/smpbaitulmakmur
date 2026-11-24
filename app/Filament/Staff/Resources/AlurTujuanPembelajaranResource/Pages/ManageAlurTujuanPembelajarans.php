<?php

namespace App\Filament\Staff\Resources\AlurTujuanPembelajaranResource\Pages;

use App\Filament\Staff\Resources\AlurTujuanPembelajaranResource;
use App\Filament\Staff\Resources\RppmResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAlurTujuanPembelajarans extends ManageRecords
{
    protected static string $resource = AlurTujuanPembelajaranResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            RppmResource::getUrl() => 'Perangkat Ajar',
            'Alur Tujuan Pembelajaran (ATP)' => null,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_perangkat_ajar')
                ->label('⬅️ Kembali ke Perangkat Ajar')
                ->color('gray')
                ->button()
                ->url(fn (): string => RppmResource::getUrl()),
            Actions\CreateAction::make()
                ->label('Tambah Alur Tujuan Pembelajaran (ATP)')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
