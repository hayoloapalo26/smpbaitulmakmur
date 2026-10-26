<?php

namespace App\Filament\Staff\Resources\PenempatanSarprasResource\Widgets;

use App\Models\PenempatanSarpras;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PenempatanSarprasStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRuangan = PenempatanSarpras::count();
        $totalUnitDitempatkan = (int) PenempatanSarpras::sum('total_unit_sarpras');
        $totalNilaiTerdistribusi = (float) PenempatanSarpras::sum('total_nilai_aset');
        $ruanganAktif = PenempatanSarpras::where('status_ruangan', 'Aktif / Digunakan')->count();

        return [
            Stat::make('Total Ruangan Terdata', $totalRuangan . ' Ruangan')
                ->description('Kelas, Lab, Kantor, & Fasilitas')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            Stat::make('Unit Sarpras Ditempatkan', number_format($totalUnitDitempatkan, 0, ',', '.') . ' Unit')
                ->description('Total fisik terdistribusi di ruangan')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Nilai Aset Terdistribusi', 'Rp ' . number_format($totalNilaiTerdistribusi, 0, ',', '.'))
                ->description('Akumulasi nilai sarpras di ruangan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Ruangan Aktif', $ruanganAktif . ' Ruang Digunakan')
                ->description('Ruangan dalam kondisi aktif fungsional')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
