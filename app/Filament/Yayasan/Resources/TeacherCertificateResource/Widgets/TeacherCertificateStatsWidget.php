<?php

namespace App\Filament\Yayasan\Resources\TeacherCertificateResource\Widgets;

use App\Models\TeacherCertificate;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeacherCertificateStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCert = TeacherCertificate::withoutGlobalScopes()->count();
        $totalGuru = TeacherCertificate::withoutGlobalScopes()->distinct('teacher_id')->count('teacher_id');
        $totalJp = (int) TeacherCertificate::withoutGlobalScopes()->sum('jumlah_jam');

        $topInst = TeacherCertificate::withoutGlobalScopes()
            ->selectRaw('institution_id, count(*) as total')
            ->whereNotNull('institution_id')
            ->groupBy('institution_id')
            ->orderByDesc('total')
            ->with('institution')
            ->first();

        $topName = $topInst?->institution?->name ?? 'Belum Ada';
        $topCount = $topInst ? " ({$topInst->total} Sertifikat)" : '';

        return [
            Stat::make('Total Sertifikat', number_format($totalCert, 0, ',', '.') . ' Sertifikat')
                ->description('Diunggah oleh guru seluruh lembaga')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('primary'),

            Stat::make('Guru Aktif Pelatihan', number_format($totalGuru, 0, ',', '.') . ' Guru')
                ->description('Memiliki portofolio sertifikat')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make('Total Jam Pelajaran (JP)', number_format($totalJp, 0, ',', '.') . ' JP')
                ->description('Akumulasi beban kompetensi')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Lembaga Teraktif', $topName)
                ->description('Paling aktif pengembangan diri' . $topCount)
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning'),
        ];
    }
}
