<?php

namespace App\Filament\Staff\Widgets;

use App\Models\AlumniTracer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AlumniTracerStatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $baseQuery = AlumniTracer::query();
        if ($user && !$user->hasRole('superadmin') && $user->institution_id != 1) {
            $baseQuery->where('institution_id', $user->institution_id);
        }

        $totalAlumni = (clone $baseQuery)->count();
        $belumTerlacak = (clone $baseQuery)->where('status_kegiatan', 'belum_terlacak')->count();
        $pendidikan = (clone $baseQuery)->whereIn('status_kegiatan', ['melanjutkan_pendidikan', 'kuliah'])->count();
        $bekerja = (clone $baseQuery)->where('status_kegiatan', 'bekerja')->count();
        $wirausaha = (clone $baseQuery)->where('status_kegiatan', 'wirausaha')->count();

        $pendidikanPct = $totalAlumni > 0 ? round(($pendidikan / $totalAlumni) * 100) : 0;
        $bekerjaPct = $totalAlumni > 0 ? round(($bekerja / $totalAlumni) * 100) : 0;
        $wirausahaPct = $totalAlumni > 0 ? round(($wirausaha / $totalAlumni) * 100) : 0;

        return [
            Stat::make('Total Alumni (Dari Data TU)', $totalAlumni)
                ->description($belumTerlacak > 0 ? "{$belumTerlacak} alumni baru belum diisi tracernya" : 'Semua alumni sudah terlacak')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color($belumTerlacak > 0 ? 'warning' : 'primary'),

            Stat::make('Lanjut Pendidikan', "{$pendidikan} ({$pendidikanPct}%)")
                ->description('SMA / SMK / MA / Perguruan Tinggi')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),

            Stat::make('Bekerja / Karier', "{$bekerja} ({$bekerjaPct}%)")
                ->description('Bekerja di instansi / swasta')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success'),

            Stat::make('Wirausaha', "{$wirausaha} ({$wirausahaPct}%)")
                ->description('Membuka usaha mandiri / bisnis')
                ->descriptionIcon('heroicon-m-rocket-launch')
                ->color('warning'),
        ];
    }
}

