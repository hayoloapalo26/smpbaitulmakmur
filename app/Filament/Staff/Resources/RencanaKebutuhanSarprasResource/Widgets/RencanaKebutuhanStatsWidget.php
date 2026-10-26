<?php

namespace App\Filament\Staff\Resources\RencanaKebutuhanSarprasResource\Widgets;

use App\Models\RencanaKebutuhanSarpras;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RencanaKebutuhanStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRencana = RencanaKebutuhanSarpras::count();
        $menunggu = RencanaKebutuhanSarpras::where('status_persetujuan', 'Menunggu Persetujuan')->count();
        $disetujui = RencanaKebutuhanSarpras::where('status_persetujuan', 'Disetujui')->count();
        $totalAnggaran = RencanaKebutuhanSarpras::sum('total_estimasi_anggaran');

        return [
            Stat::make('Total Rencana Kebutuhan', $totalRencana . ' Pengajuan')
                ->description('Seluruh dokumen perencanaan kebutuhan sarpras')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Menunggu ACC Kepala Sekolah', $menunggu . ' Menunggu')
                ->description('Rencana kebutuhan menunggu verifikasi KS')
                ->descriptionIcon('heroicon-m-clock')
                ->color($menunggu > 0 ? 'warning' : 'gray'),

            Stat::make('Rencana Disetujui (ACC)', $disetujui . ' Disetujui')
                ->description('Siap diajukan ke tahap pengadaan aset')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Estimasi Anggaran', 'Rp ' . number_format($totalAnggaran, 0, ',', '.'))
                ->description('Kalkulasi nilai kebutuhan sarpras')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
        ];
    }
}
