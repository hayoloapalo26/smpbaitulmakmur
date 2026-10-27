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
        $individualCount = (clone $baseQuery)->where(function ($q) {
            $q->where('lingkup', 'individual')->orWhereNull('lingkup');
        })->count();
        $kelasCount = (clone $baseQuery)->where('lingkup', 'kelas')->count();

        $nonAkademik = (clone $baseQuery)->where('kategori', 'non_akademik')->count();
        $prestasiKelas = (clone $baseQuery)->where('kategori', 'prestasi_kelas')->count();
        $akademik = (clone $baseQuery)->where('kategori', 'akademik')->count();

        $religiusKelas = (clone $baseQuery)->where('kategori', 'prestasi_kelas')->where('sub_kategori', 'religius')->count();
        $karakterKelas = (clone $baseQuery)->where('kategori', 'prestasi_kelas')->where('sub_kategori', 'karakter')->count();
        $adabKelas = (clone $baseQuery)->where('kategori', 'prestasi_kelas')->where('sub_kategori', 'adab')->count();

        $rewardReligi = (clone $baseQuery)->where('jenis_reward', 'reward_religi')->count();
        $rewardAkademik = (clone $baseQuery)->where('jenis_reward', 'reward_akademik')->count();
        $totalReward = $rewardReligi + $rewardAkademik;

        $nonAkademikPct = $totalPrestasi > 0 ? round(($nonAkademik / $totalPrestasi) * 100) : 0;
        $akademikPct = $totalPrestasi > 0 ? round(($akademik / $totalPrestasi) * 100) : 0;
        $prestasiKelasPct = $totalPrestasi > 0 ? round(($prestasiKelas / $totalPrestasi) * 100) : 0;

        return [
            Stat::make('Total Prestasi', $totalPrestasi)
                ->description("{$individualCount} Individual · {$kelasCount} Rombel Kelas")
                ->descriptionIcon('heroicon-m-trophy')
                ->color('primary'),

            Stat::make('Prestasi Non-Akademik', "{$nonAkademik} ({$nonAkademikPct}%)")
                ->description('Seni, Olahraga, Pramuka, Bakat Minat')
                ->descriptionIcon('heroicon-m-fire')
                ->color('warning'),

            Stat::make('Prestasi Kelas', "{$prestasiKelas} ({$prestasiKelasPct}%)")
                ->description("Religius: {$religiusKelas} · Karakter: {$karakterKelas} · Adab: {$adabKelas}")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Prestasi Akademik', "{$akademik} ({$akademikPct}%)")
                ->description('Sains, Matematika, Mapel, Riset/KIR')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Reward Kesiswaan', "{$totalReward} Diberikan")
                ->description("{$rewardReligi} Reward Religi · {$rewardAkademik} Reward Akademik")
                ->descriptionIcon('heroicon-m-gift')
                ->color('emerald'),
        ];
    }
}
