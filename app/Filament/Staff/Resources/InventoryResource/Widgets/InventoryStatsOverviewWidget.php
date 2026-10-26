<?php

namespace App\Filament\Staff\Resources\InventoryResource\Widgets;

use App\Models\Inventory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalJenis = Inventory::count();
        $totalUnit = (int) Inventory::sum('jumlah_total');
        $totalNilaiAset = (float) Inventory::sum('total_nilai_aset');

        $totalBaik = (int) Inventory::sum('jumlah_kondisi_baik');
        $totalRusakRingan = (int) Inventory::sum('jumlah_kondisi_rusak_ringan');
        $totalRusakBerat = (int) Inventory::sum('jumlah_kondisi_rusak_berat');

        return [
            Stat::make('Total Jenis Sarpras', $totalJenis . ' Barang')
                ->description('Ragam sarana & prasarana terdata')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('info'),

            Stat::make('Total Unit Fisik', number_format($totalUnit, 0, ',', '.') . ' Unit')
                ->description('Akumulasi seluruh unit aset')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Total Nilai Aset', 'Rp ' . number_format($totalNilaiAset, 0, ',', '.'))
                ->description('Estimasi nilai inventaris lembaga')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Kondisi Sarpras', "{$totalBaik} Baik")
                ->description("{$totalRusakRingan} Rusak Ringan • {$totalRusakBerat} Rusak Berat")
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color($totalRusakBerat > 0 ? 'danger' : ($totalRusakRingan > 0 ? 'warning' : 'success')),
        ];
    }
}
