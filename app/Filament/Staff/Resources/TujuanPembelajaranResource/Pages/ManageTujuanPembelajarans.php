<?php

namespace App\Filament\Staff\Resources\TujuanPembelajaranResource\Pages;

use App\Filament\Staff\Resources\RppmResource;
use App\Filament\Staff\Resources\TujuanPembelajaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTujuanPembelajarans extends ManageRecords
{
    protected static string $resource = TujuanPembelajaranResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            RppmResource::getUrl() => 'Perangkat Ajar',
            'Tujuan Pembelajaran (TP)' => null,
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
            Actions\Action::make('cetak_rekap_pdf')
                ->label('🖨️ Cetak Rekap TP (PDF)')
                ->color('success')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('academic.tp.rekap.pdf'))
                ->openUrlInNewTab(),
            Actions\CreateAction::make()
                ->label('Tambah Tujuan Pembelajaran (TP)')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
