<?php

namespace App\Filament\Staff\Resources\PresensiIzinSiswaResource\Pages;

use App\Filament\Staff\Resources\PresensiIzinSiswaResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManagePresensiIzinSiswa extends ManageRecords
{
    protected static string $resource = PresensiIzinSiswaResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Presensi'),
            'masuk' => Tab::make('Kehadiran Harian')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipe_absen', 'masuk')),
            'sholat' => Tab::make('Absensi Sholat')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('tipe_absen', ['sholat_dhuha', 'sholat_dhuhur', 'sholat_ashar'])),
            'sakit' => Tab::make('🤒 Sakit')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sakit')),
            'izin' => Tab::make('📝 Izin')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'izin')),
            'alpa' => Tab::make('❌ Alpha')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'alpa')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('rekap_lengkap_siswa')
                ->label('Rekap Total Absensi & Sholat Siswa')
                ->icon('heroicon-o-chart-bar-square')
                ->color('warning')
                ->url(fn () => \App\Filament\Staff\Pages\PrayerAttendanceReport::getUrl())
                ->visible(fn () => auth()->user()->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'wali_kelas', 'guru_kelas', 'tata_usaha', 'superadmin'])),
            Actions\Action::make('absensi_kolektif')
                ->label('Input Presensi Kolektif Per Kelas')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('info')
                ->url(fn () => \App\Filament\Staff\Pages\AbsensiManual::getUrl())
                ->visible(fn () => auth()->user()->hasAnyRole(['guru_piket', 'tata_usaha', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin'])),
            Actions\CreateAction::make()
                ->label('Catat Hadir / Izin / Sakit / Alpha')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
