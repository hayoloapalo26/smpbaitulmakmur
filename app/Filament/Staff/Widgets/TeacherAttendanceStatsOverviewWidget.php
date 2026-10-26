<?php

namespace App\Filament\Staff\Widgets;

use App\Models\TeacherAttendance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TeacherAttendanceStatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $query = TeacherAttendance::withoutGlobalScope('institution');

        // Jika bukan waka_kurikulum, superadmin, kepsek, atau tata_usaha, batasi ke user yang bersangkutan
        if ($user && !$user->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah', 'tata_usaha'])) {
            $query->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                  ->orWhere('substitute_teacher_id', $user->id);
            });
        }

        $totalHadir = (clone $query)->where('status', 'hadir')->count();
        $totalIzin = (clone $query)->where('status', 'izin')->count();
        $totalSakit = (clone $query)->where('status', 'sakit')->count();
        $totalAlpa = (clone $query)->where('status', 'alpa')->count();
        $totalDinas = (clone $query)->where('status', 'dinas_luar')->count();
        $totalPengganti = (clone $query)->whereNotNull('substitute_teacher_id')->count();

        $totalSesi = $totalHadir + $totalIzin + $totalSakit + $totalAlpa + $totalDinas;
        $persenHadir = $totalSesi > 0 ? round(($totalHadir / $totalSesi) * 100, 1) : 0;

        return [
            Stat::make('Total Jam Hadir Mengajar', "{$totalHadir} JP / Sesi")
                ->description('Jam tatap muka guru di kelas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Jam Izin & Sakit', ($totalIzin + $totalSakit + $totalDinas) . ' JP / Sesi')
                ->description("Izin: {$totalIzin} | Sakit: {$totalSakit} | Dinas: {$totalDinas}")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),

            Stat::make('Jam Diinval / Pengganti', "{$totalPengganti} JP / Sesi")
                ->description('Jam kelas ditangani guru pengganti')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),

            Stat::make('Tingkat Kehadiran Guru', "{$persenHadir}%")
                ->description("Dari total {$totalSesi} sesi jam pelajaran terjadwal")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($persenHadir >= 90 ? 'success' : ($persenHadir >= 75 ? 'warning' : 'danger')),
        ];
    }
}
