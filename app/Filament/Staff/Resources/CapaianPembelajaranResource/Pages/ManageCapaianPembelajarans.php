<?php

namespace App\Filament\Staff\Resources\CapaianPembelajaranResource\Pages;

use App\Filament\Staff\Resources\CapaianPembelajaranResource;
use App\Filament\Staff\Resources\RppmResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCapaianPembelajarans extends ManageRecords
{
    protected static string $resource = CapaianPembelajaranResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            RppmResource::getUrl() => 'Perangkat Ajar',
            'Capaian Pembelajaran (CP)' => null,
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
                ->label('🖨️ Cetak Rekap CP (PDF)')
                ->color('success')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('academic.cp.rekap.pdf'))
                ->openUrlInNewTab(),
            Actions\CreateAction::make()
                ->label('Tambah Capaian Pembelajaran (CP)')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
