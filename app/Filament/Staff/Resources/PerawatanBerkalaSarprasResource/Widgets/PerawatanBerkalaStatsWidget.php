<?php

namespace App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource\Widgets;

use App\Models\PerawatanBerkalaSarpras;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PerawatanBerkalaStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $bulanMap = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $currentMonthName = $bulanMap[(int) date('n')] ?? 'Januari';
        $currentYear = (int) date('Y');

        $laporanBulanIni = PerawatanBerkalaSarpras::where('periode_bulan', $currentMonthName)
            ->where('periode_tahun', $currentYear)
            ->count();

        $totalUnitDiperiksa = (int) PerawatanBerkalaSarpras::sum('total_unit_diperiksa');
        $totalKondisiBaik = (int) PerawatanBerkalaSarpras::sum('total_kondisi_baik');
        $totalRusak = (int) (PerawatanBerkalaSarpras::sum('total_kondisi_rusak_ringan') + PerawatanBerkalaSarpras::sum('total_kondisi_rusak_berat'));
        $totalBiaya = (float) PerawatanBerkalaSarpras::sum('total_biaya_pemeliharaan');

        $rasioKelaikan = $totalUnitDiperiksa > 0 
            ? round(($totalKondisiBaik / $totalUnitDiperiksa) * 100, 1) 
            : 100;

        return [
            Stat::make('Laporan Bulan Ini', $laporanBulanIni . ' Laporan')
                ->description("Periode {$currentMonthName} {$currentYear}")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Unit Sarpras Diperiksa', number_format($totalUnitDiperiksa, 0, ',', '.') . ' Unit')
                ->description("Total sarana dicek & dirawat")
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('info'),

            Stat::make('Tingkat Kelaikan Pasca Servis', $rasioKelaikan . '% Layak')
                ->description("{$totalKondisiBaik} Baik vs {$totalRusak} Perlu Perbaikan")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($rasioKelaikan >= 85 ? 'success' : 'warning'),

            Stat::make('Total Realisasi Biaya', 'Rp ' . number_format($totalBiaya, 0, ',', '.'))
                ->description('Biaya jasa teknisi & suku cadang')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
