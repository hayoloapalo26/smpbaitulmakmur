<?php

namespace App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource\Widgets;

use App\Models\PerbaikanPenggantianSarpras;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PerbaikanPenggantianStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalKasus = PerbaikanPenggantianSarpras::count();

        $dalamProses = PerbaikanPenggantianSarpras::whereIn('status_proses', [
            'Dilaporkan',
            'Sedang Diperbaiki',
            'Menunggu Penggantian Unit',
        ])->count();

        $selesai = PerbaikanPenggantianSarpras::whereIn('status_proses', [
            'Selesai Diperbaiki',
            'Selesai Diganti Unit Baru',
        ])->count();

        $afkirHilang = PerbaikanPenggantianSarpras::where('status_proses', 'Dihapuskan / Afkir')
            ->orWhere('jenis_kasus', 'Hilang / Tidak Ditemukan')
            ->count();

        $totalBiaya = (float) PerbaikanPenggantianSarpras::sum('realisasi_biaya');

        return [
            Stat::make('Total Kasus Kerusakan / Hilang', $totalKasus . ' Kasus')
                ->description('Total pelaporan sarpras bermasalah')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Sedang Dalam Penanganan', $dalamProses . ' Kasus Aktif')
                ->description('Dalam perbaikan atau antrean penggantian')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($dalamProses > 0 ? 'warning' : 'success'),

            Stat::make('Selesai Ditangani', $selesai . ' Kasus Tuntas')
                ->description('Unit telah siap & kembali berfungsi')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Realisasi Biaya Penanganan', 'Rp ' . number_format($totalBiaya, 0, ',', '.'))
                ->description('Total servis & pengadaan pengganti')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}
