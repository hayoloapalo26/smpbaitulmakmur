<?php

namespace App\Filament\Staff\Resources\TeacherAttendanceResource\Pages;

use App\Filament\Staff\Resources\TeacherAttendanceResource;
use App\Filament\Staff\Widgets\TeacherAttendanceStatsOverviewWidget;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ManageTeacherAttendances extends ManageRecords
{
    protected static string $resource = TeacherAttendanceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TeacherAttendanceStatsOverviewWidget::class,
            \App\Filament\Staff\Widgets\TeacherMonthlyWorkloadWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('rekap_jam_guru')
                ->label('Laporan Rekap Jam Guru (Cetak / PDF)')
                ->icon('heroicon-m-printer')
                ->color('primary')
                ->modalHeading('Laporan Rekapitulasi Presensi & Beban Jam Mengajar Guru')
                ->modalWidth('7xl')
                ->form([
                    \Filament\Forms\Components\Grid::make(2)->schema([
                        \Filament\Forms\Components\Select::make('bulan')
                            ->label('Pilih Periode Bulan')
                            ->options([
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                            ])
                            ->default((int) date('m'))
                            ->required()
                            ->live(),
                        \Filament\Forms\Components\Select::make('tahun')
                            ->label('Pilih Tahun')
                            ->options([
                                2025 => '2025',
                                2026 => '2026',
                                2027 => '2027',
                            ])
                            ->default((int) date('Y'))
                            ->required()
                            ->live(),
                    ]),
                ])
                ->modalContent(function (array $data) {
                    $bulan = !empty($data['bulan']) ? (int) $data['bulan'] : (int) date('m');
                    $tahun = !empty($data['tahun']) ? (int) $data['tahun'] : (int) date('Y');
                    return view('filament.staff.modals.teacher-attendance-report', [
                        'selectedMonth' => $bulan,
                        'selectedYear' => $tahun,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Presensi & Izin')
                ->icon('heroicon-m-list-bullet'),

            'hadir' => Tab::make('Masuk Mengajar')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['masuk', 'hadir'])),

            'izin_sakit' => Tab::make('Izin & Sakit')
                ->icon('heroicon-m-calendar-days')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['izin', 'sakit', 'alpa', 'dinas_luar'])),

            'ada_pengganti' => Tab::make('Ada Guru Pengganti')
                ->icon('heroicon-m-user-plus')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('substitute_teacher_id')),

            'belum_ada_pengganti' => Tab::make('Belum Ada Pengganti')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['izin', 'sakit', 'alpa', 'dinas_luar'])->whereNull('substitute_teacher_id')),
        ];
    }
}
