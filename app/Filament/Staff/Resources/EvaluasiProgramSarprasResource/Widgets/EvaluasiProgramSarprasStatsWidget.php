<?php

namespace App\Filament\Staff\Resources\EvaluasiProgramSarprasResource\Widgets;

use App\Models\EvaluasiProgramSarpras;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EvaluasiProgramSarprasStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalEvaluasi = EvaluasiProgramSarpras::count();

        $avgKetercapaian = EvaluasiProgramSarpras::count() > 0 
            ? round(EvaluasiProgramSarpras::avg('persentase_ketercapaian'), 1) 
            : 100;

        $terlaksanaPenuh = EvaluasiProgramSarpras::where('status_keterlaksanaan', 'Terlaksana Sepenuhnya')->count();
        $totalRealisasiBiaya = (float) EvaluasiProgramSarpras::sum('realisasi_anggaran');

        return [
            Stat::make('Total Program Dievaluasi', $totalEvaluasi . ' Program')
                ->description('Evaluasi keterlaksanaan sarpras')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('primary'),

            Stat::make('Rata-rata Ketercapaian', $avgKetercapaian . '%')
                ->description('Tingkat pemenuhan target program')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($avgKetercapaian >= 80 ? 'success' : 'warning'),

            Stat::make('Terlaksana Sepenuhnya', $terlaksanaPenuh . ' Program')
                ->description('Program sukses 100% terlaksana')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Total Realisasi Anggaran', 'Rp ' . number_format($totalRealisasiBiaya, 0, ',', '.'))
                ->description('Akumulasi serapan biaya program')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}
