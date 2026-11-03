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
                    \Filament\Forms\Components\Grid::make(3)->schema([
                        \Filament\Forms\Components\Select::make('bulan')
                            ->label('Pilih Periode Bulan')
                            ->options([
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                            ])
                            ->default((int) date('m'))
                            ->searchable()
                            ->required()
                            ->live(),
                        \Filament\Forms\Components\Select::make('tahun')
                            ->label('Pilih Tahun')
                            ->options([
                                2024 => '2024',
                                2025 => '2025',
                                2026 => '2026',
                                2027 => '2027',
                                2028 => '2028',
                            ])
                            ->default((int) date('Y'))
                            ->searchable()
                            ->required()
                            ->live(),
                        \Filament\Forms\Components\Select::make('teacher_id')
                            ->label('Pencarian / Filter Guru')
                            ->placeholder('-- Semua Guru (Tampilkan Seluruh Rekap) --')
                            ->options(fn () => \App\Models\User::withoutGlobalScopes()
                                ->where(function ($q) {
                                    $q->whereHas('roles', fn ($r) => $r->whereIn('name', array_merge(['guru_mapel', 'wali_kelas', 'guru_kelas', 'waka_kurikulum'], \App\Models\User::QURAN_TEACHER_ROLES)))
                                      ->orWhereHas('schedules');
                                })
                                ->where('name', 'not like', '%piket%')
                                ->where(fn ($q) => $q->whereNull('username')->orWhere('username', 'not like', '%piket%'))
                                ->where('email', 'not like', '%piket%')
                                ->where('email', 'not like', '%@smp.com')
                                ->whereNotIn('id', [1, 2, 3, 25, 30, 31, 32])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
                ])
                ->modalContent(function (array $data) {
                    $bulan = !empty($data['bulan']) ? (int) $data['bulan'] : (int) date('m');
                    $tahun = !empty($data['tahun']) ? (int) $data['tahun'] : (int) date('Y');
                    $teacherId = !empty($data['teacher_id']) ? (int) $data['teacher_id'] : null;
                    return view('filament.staff.modals.teacher-attendance-report', [
                        'selectedMonth' => $bulan,
                        'selectedYear' => $tahun,
                        'selectedTeacherId' => $teacherId,
                    ]);
                })
                ->modalSubmitAction(fn ($action) => $action
                    ->label('Cari / Tampilkan Data')
                    ->icon('heroicon-m-magnifying-glass')
                    ->color('primary')
                )
                ->action(function (Actions\Action $action, array $data) {
                    $bulan = !empty($data['bulan']) ? (int) $data['bulan'] : (int) date('m');
                    $tahun = !empty($data['tahun']) ? (int) $data['tahun'] : (int) date('Y');
                    \Filament\Notifications\Notification::make()
                        ->title('Data Rekapitulasi Berhasil Ditampilkan')
                        ->body("Menampilkan rekapitulasi presensi guru periode " . \Carbon\Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y') . ".")
                        ->success()
                        ->send();

                    $action->halt();
                })
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
