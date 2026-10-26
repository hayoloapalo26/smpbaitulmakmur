<?php

namespace App\Filament\Staff\Widgets;

use App\Models\StudentAchievement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentAchievementStatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $baseQuery = StudentAchievement::query();
        if ($user && !$user->hasRole('superadmin') && $user->institution_id != 1) {
            $baseQuery->where('institution_id', $user->institution_id);
        }

        $totalPrestasi = (clone $baseQuery)->count();
        $akademik = (clone $baseQuery)->where('kategori', 'akademik')->count();
        $religius = (clone $baseQuery)->where('kategori', 'religius')->count();
        $nonAkademik = (clone $baseQuery)->where('kategori', 'non_akademik')->count();

        $akademikPct = $totalPrestasi > 0 ? round(($akademik / $totalPrestasi) * 100) : 0;
        $religiusPct = $totalPrestasi > 0 ? round(($religius / $totalPrestasi) * 100) : 0;
        $nonAkademikPct = $totalPrestasi > 0 ? round(($nonAkademik / $totalPrestasi) * 100) : 0;

        return [
            Stat::make('Total Prestasi Siswa', $totalPrestasi)
                ->description('Prestasi tercatat di lembaga ini')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('primary'),

            Stat::make('Prestasi Akademik', "{$akademik} ({$akademikPct}%)")
                ->description('Sains, Matematika, Mapel, KIR')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Prestasi Religius', "{$religius} ({$religiusPct}%)")
                ->description('Tahfidz, Tahsin, MTQ/MHQ, Keagamaan')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('success'),

            Stat::make('Prestasi Non-Akademik', "{$nonAkademik} ({$nonAkademikPct}%)")
                ->description('Seni, Olahraga, Pidato, Pramuka')
                ->descriptionIcon('heroicon-m-fire')
                ->color('warning'),
        ];
    }
}
