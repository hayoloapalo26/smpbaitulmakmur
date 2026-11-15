<?php

namespace App\Filament\Staff\Resources\PresensiIzinSiswaResource\Pages;

use App\Filament\Staff\Resources\FingerspotDeviceResource;
use App\Filament\Staff\Resources\PresensiIzinSiswaResource;
use App\Filament\Staff\Resources\PresensiIzinSiswaResource\Widgets\DailyAttendanceStatsOverviewWidget;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class ManagePresensiIzinSiswa extends ManageRecords
{
    protected static string $resource = PresensiIzinSiswaResource::class;

    public string $mode = 'harian'; // 'harian' atau 'rentang'
    public string $filterTanggal = '';
    public string $filterTanggalMulai = '';
    public string $filterTanggalSelesai = '';

    public function mount(): void
    {
        parent::mount();
        $this->filterTanggal = Carbon::today()->toDateString();
        $this->filterTanggalMulai = Carbon::today()->startOfMonth()->toDateString();
        $this->filterTanggalSelesai = Carbon::today()->toDateString();
    }

    #[On('mode-changed')]
    public function onModeChanged(string $mode): void
    {
        $this->mode = $mode;
        $this->resetTable();
    }

    #[On('date-changed')]
    public function onDateChanged(string $newDate): void
    {
        $this->filterTanggal = $newDate;
        $this->resetTable();
    }

    #[On('date-range-changed')]
    public function onDateRangeChanged(string $startDate, string $endDate): void
    {
        $this->filterTanggalMulai = $startDate;
        $this->filterTanggalSelesai = $endDate;
        $this->resetTable();
    }

    #[On('refresh-presensi-table')]
    #[On('fingerspot-synced')]
    public function refreshTableData(): void
    {
        $this->resetTable();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DailyAttendanceStatsOverviewWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $isRentang = $this->mode === 'rentang';
        $startDate = $this->filterTanggalMulai ?: Carbon::today()->startOfMonth()->toDateString();
        $endDate = $this->filterTanggalSelesai ?: Carbon::today()->toDateString();
        $date = $this->filterTanggal ?: Carbon::today()->toDateString();

        $tabs = [
            'all' => Tab::make('Semua Siswa Aktif'),

            'lengkap' => Tab::make('Hadir Lengkap (3/3)')
                ->modifyQueryUsing(function (Builder $query) use ($isRentang, $startDate, $endDate, $date) {
                    if ($isRentang) {
                        $query->whereHas('dailyAttendances', function ($q) use ($startDate, $endDate) {
                            $q->withoutGlobalScopes()
                              ->whereBetween('tanggal', [$startDate, $endDate])
                              ->where('tipe_absen', 'masuk')
                              ->where('status', 'hadir');
                        });
                    } else {
                        $query->whereHas('dailyAttendances', function ($q) use ($date) {
                            $q->withoutGlobalScopes()
                              ->whereDate('tanggal', $date)
                              ->where('tipe_absen', 'masuk')
                              ->where('status', 'hadir');
                        })->where(function ($q) use ($date) {
                            $q->whereHas('dailyAttendances', function ($sub) use ($date) {
                                $sub->withoutGlobalScopes()
                                    ->whereDate('tanggal', $date)
                                    ->where('tipe_absen', 'sholat_dhuhur')
                                    ->where('status', 'hadir');
                            })->orWhere(function ($sub) use ($date) {
                                $sub->where('jenis_kelamin', 'P')
                                    ->whereHas('dailyAttendances', function ($h) use ($date) {
                                        $h->withoutGlobalScopes()->whereDate('tanggal', $date)->where('status', 'halangan');
                                    });
                            });
                        });
                    }
                }),

            'terlambat' => Tab::make('Terlambat Masuk')
                ->badgeColor('danger')
                ->modifyQueryUsing(function (Builder $query) use ($isRentang, $startDate, $endDate, $date) {
                    $query->whereHas('dailyAttendances', function ($q) use ($isRentang, $startDate, $endDate, $date) {
                        $q->withoutGlobalScopes();
                        if ($isRentang) {
                            $q->whereBetween('tanggal', [$startDate, $endDate]);
                        } else {
                            $q->whereDate('tanggal', $date);
                        }
                        $q->where('tipe_absen', 'masuk')
                          ->where('status', 'hadir')
                          ->where(function ($sub) {
                              $sub->where('is_late', true)
                                  ->orWhere('waktu_kedatangan', '>', '06:50:00');
                          });
                    });
                }),
        ];

        // Tab "Belum Scan / Sholat" hanya ditampilkan pada Mode Harian
        if (!$isRentang) {
            $tabs['belum_masuk'] = Tab::make('Belum Scan Masuk')
                ->badgeColor('warning')
                ->modifyQueryUsing(function (Builder $query) use ($date) {
                    $query->whereDoesntHave('dailyAttendances', function ($q) use ($date) {
                        $q->withoutGlobalScopes()
                          ->whereDate('tanggal', $date)
                          ->where(function ($sub) {
                              $sub->where('tipe_absen', 'masuk')
                                  ->orWhereIn('status', ['sakit', 'izin', 'alpa']);
                          });
                    });
                });

            $tabs['belum_dhuhur'] = Tab::make('Belum Sholat Dhuhur')
                ->modifyQueryUsing(function (Builder $query) use ($date) {
                    $query->whereDoesntHave('dailyAttendances', function ($q) use ($date) {
                        $q->withoutGlobalScopes()
                          ->whereDate('tanggal', $date)
                          ->where(function ($sub) {
                              $sub->where('tipe_absen', 'sholat_dhuhur')
                                  ->orWhereIn('status', ['sakit', 'izin', 'alpa', 'halangan']);
                          });
                    });
                });

            $tabs['belum_ashar'] = Tab::make('Belum Sholat Ashar')
                ->modifyQueryUsing(function (Builder $query) use ($date) {
                    $query->whereDoesntHave('dailyAttendances', function ($q) use ($date) {
                        $q->withoutGlobalScopes()
                          ->whereDate('tanggal', $date)
                          ->where(function ($sub) {
                              $sub->whereIn('tipe_absen', ['sholat_ashar', 'pulang'])
                                  ->orWhereIn('status', ['sakit', 'izin', 'alpa', 'halangan']);
                          });
                    });
                });
        }

        $tabs['izin_sakit'] = Tab::make('Izin / Sakit / Alpa')
            ->badgeColor('info')
            ->modifyQueryUsing(function (Builder $query) use ($isRentang, $startDate, $endDate, $date) {
                $query->whereHas('dailyAttendances', function ($q) use ($isRentang, $startDate, $endDate, $date) {
                    $q->withoutGlobalScopes();
                    if ($isRentang) {
                        $q->whereBetween('tanggal', [$startDate, $endDate]);
                    } else {
                        $q->whereDate('tanggal', $date);
                    }
                    $q->whereIn('status', ['sakit', 'izin', 'alpa']);
                });
            });

        $tabs['halangan'] = Tab::make('Halangan Siswi')
            ->modifyQueryUsing(function (Builder $query) use ($isRentang, $startDate, $endDate, $date) {
                $query->where('jenis_kelamin', 'P')
                      ->where(function ($q) use ($isRentang, $startDate, $endDate, $date) {
                          $q->whereHas('dailyAttendances', function ($sub) use ($isRentang, $startDate, $endDate, $date) {
                              $sub->withoutGlobalScopes();
                              if ($isRentang) {
                                  $sub->whereBetween('tanggal', [$startDate, $endDate]);
                              } else {
                                  $sub->whereDate('tanggal', $date);
                              }
                              $sub->where('status', 'halangan');
                          })->orWhereHas('halangans', function ($sub) use ($isRentang, $startDate, $endDate, $date) {
                              if ($isRentang) {
                                  $sub->where('tanggal_mulai', '<=', $endDate)
                                      ->where('tanggal_selesai', '>=', $startDate);
                              } else {
                                  $sub->where('tanggal_mulai', '<=', $date)
                                      ->where('tanggal_selesai', '>=', $date);
                              }
                          });
                      });
            });

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            // 1. Cetak & Unduh PDF Rekap Presensi Resmi (Menyesuaikan Mode Harian atau Rentang Tanggal)
            Actions\Action::make('cetak_pdf')
                ->label(fn () => $this->mode === 'rentang' ? 'Cetak PDF Rentang Tanggal' : 'Cetak PDF Presensi Harian')
                ->icon('heroicon-o-printer')
                ->color('danger')
                ->url(function () {
                    if ($this->mode === 'rentang') {
                        return route('daily-attendance.rekap.pdf', [
                            'mode'            => 'rentang',
                            'tanggal_mulai'   => $this->filterTanggalMulai ?: Carbon::today()->startOfMonth()->toDateString(),
                            'tanggal_selesai' => $this->filterTanggalSelesai ?: Carbon::today()->toDateString(),
                        ]);
                    }

                    return route('daily-attendance.rekap.pdf', [
                        'mode'    => 'harian',
                        'tanggal' => $this->filterTanggal ?: Carbon::today()->toDateString(),
                    ]);
                })
                ->openUrlInNewTab(),

            // 2. Input Presensi Kolektif Per Kelas
            Actions\Action::make('presensi_kolektif')
                ->label('Presensi Kolektif Rombel')
                ->icon('heroicon-o-user-group')
                ->color('primary')
                ->modalHeading('Input Presensi Kolektif Satu Rombel / Kelas')
                ->modalDescription('Fitur cepat untuk menandai satu rombel kelas hadir atau izin bersamaan (misal kegiatan outbond/studi lapangan).')
                ->form([
                    Forms\Components\Select::make('classroom_id')
                        ->label('Pilih Kelas / Rombel')
                        ->options(fn () => Classroom::orderBy('name')->pluck('name', 'id'))
                        ->required(),

                    Forms\Components\DatePicker::make('tanggal')
                        ->label('Tanggal')
                        ->default(fn () => $this->filterTanggal ?: Carbon::today()->toDateString())
                        ->required(),

                    Forms\Components\Select::make('tipe_absen')
                        ->label('Sesi Presensi')
                        ->options([
                            'masuk'         => '🏫 Kedatangan Sekolah (Masuk)',
                            'sholat_dhuhur' => '🕌 Sholat Dhuhur',
                            'sholat_ashar'  => '🕌 Sholat Ashar',
                            'semua'         => '✨ Seluruh Sesi (Masuk, Dhuhur, Ashar)',
                        ])
                        ->default('masuk')
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->label('Status Kehadiran')
                        ->options([
                            'hadir' => '✅ Hadir Bersama',
                            'izin'  => '📋 Izin Kegiatan Sekolah / Dinas',
                        ])
                        ->default('hadir')
                        ->required(),

                    Forms\Components\TimePicker::make('waktu_kedatangan')
                        ->label('Jam Kehadiran (Jika Hadir)')
                        ->default('06:40'),

                    Forms\Components\TextInput::make('keterangan')
                        ->label('Keterangan / Catatan')
                        ->placeholder('Contoh: Mengikuti kegiatan manasik haji / outbond yayasan')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $students = Student::withoutGlobalScopes()
                        ->where('classroom_id', $data['classroom_id'])
                        ->where('status_siswa', 'aktif')
                        ->get();

                    $sesiList = $data['tipe_absen'] === 'semua'
                        ? ['masuk', 'sholat_dhuhur', 'sholat_ashar']
                        : [$data['tipe_absen']];

                    $count = 0;
                    foreach ($students as $student) {
                        foreach ($sesiList as $sesi) {
                            DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                [
                                    'student_id' => $student->id,
                                    'tanggal'    => $data['tanggal'],
                                    'tipe_absen' => $sesi,
                                ],
                                [
                                    'institution_id'   => $student->institution_id ?: 2,
                                    'teacher_id'       => Auth::id(),
                                    'status'           => $data['status'],
                                    'metode_absen'     => 'manual',
                                    'waktu_kedatangan' => $data['status'] === 'hadir' ? $data['waktu_kedatangan'] : null,
                                    'is_late'          => false,
                                    'keterangan'       => $data['keterangan'],
                                ]
                            );
                        }
                        $count++;
                    }

                    Notification::make()
                        ->title('Presensi Kolektif Berhasil')
                        ->body("Berhasil mencatat presensi kolektif untuk {$count} siswa.")
                        ->success()
                        ->send();

                    $this->resetTable();
                    $this->dispatch('refresh-presensi-table');
                }),

            // 3. Tandai Halangan Siswi Putri (Header Action)
            Actions\Action::make('tandai_halangan_siswi')
                ->label('Tandai Halangan Siswi')
                ->icon('heroicon-o-heart')
                ->color('purple')
                ->modalHeading('Tandai Halangan Sholat Siswi Putri')
                ->modalDescription("Tandai siswi-siswi putri yang sedang udzur syar'i (haid). Absensi Sholat Dhuhur & Ashar otomatis dicatat 'Sedang Halangan', sedangkan absensi kedatangan tetap tercatat normal.")
                ->form([
                    Forms\Components\Select::make('student_ids')
                        ->label('Pilih Siswi Putri')
                        ->multiple()
                        ->searchable()
                        ->options(function () {
                            $user = Auth::user();
                            $query = Student::withoutGlobalScopes()
                                ->where('status_siswa', 'aktif')
                                ->where('jenis_kelamin', 'P');
                            if ($user && !$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
                                $query->where('institution_id', $user->institution_id);
                            }
                            return $query->orderBy('name')->pluck('name', 'id');
                        })
                        ->required(),

                    Forms\Components\DatePicker::make('tanggal_mulai')
                        ->label('Tanggal Mulai Halangan')
                        ->default(fn () => $this->filterTanggal ?: Carbon::today()->toDateString())
                        ->required(),

                    Forms\Components\Select::make('durasi_hari')
                        ->label('Estimasi Lama Halangan')
                        ->options([
                            1  => '1 Hari (Hari Ini Saja)',
                            3  => '3 Hari',
                            5  => '5 Hari',
                            7  => '7 Hari (Standar Siklus)',
                            10 => '10 Hari',
                            14 => '14 Hari (Maksimal)',
                        ])
                        ->default(7)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            $start = $get('tanggal_mulai') ?: now()->toDateString();
                            $days = (int) ($state ?: 7);
                            $set('tanggal_selesai', Carbon::parse($start)->addDays($days - 1)->toDateString());
                        }),

                    Forms\Components\DatePicker::make('tanggal_selesai')
                        ->label('Tanggal Selesai Halangan')
                        ->default(fn () => Carbon::parse($this->filterTanggal ?: Carbon::today()->toDateString())->addDays(6)->toDateString())
                        ->required(),

                    Forms\Components\TextInput::make('keterangan')
                        ->label('Keterangan / Catatan')
                        ->default("Sedang halangan / udzur syar'i")
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $start = $data['tanggal_mulai'];
                    $end = $data['tanggal_selesai'] ?? $start;
                    $studentIds = $data['student_ids'] ?? [];
                    $count = 0;

                    foreach ($studentIds as $stId) {
                        $student = Student::withoutGlobalScopes()->find($stId);
                        if (!$student) continue;

                        \App\Models\StudentHalangan::create([
                            'student_id'      => $student->id,
                            'institution_id'  => $student->institution_id ?: 2,
                            'tanggal_mulai'   => $start,
                            'tanggal_selesai' => $end,
                            'catatan'         => $data['keterangan'] ?? "Sedang halangan / udzur syar'i",
                            'recorded_by'     => Auth::id() ?: 1,
                        ]);

                        $period = \Carbon\CarbonPeriod::create(Carbon::parse($start), Carbon::parse($end));
                        foreach ($period as $dt) {
                            $curDate = $dt->toDateString();
                            foreach (['sholat_dhuhur', 'sholat_ashar'] as $sesi) {
                                DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                    [
                                        'student_id' => $student->id,
                                        'tanggal'    => $curDate,
                                        'tipe_absen' => $sesi,
                                    ],
                                    [
                                        'institution_id'   => $student->institution_id ?: 2,
                                        'teacher_id'       => Auth::id() ?: 1,
                                        'status'           => 'halangan',
                                        'metode_absen'     => 'manual',
                                        'waktu_kedatangan' => null,
                                        'is_late'          => false,
                                        'keterangan'       => $data['keterangan'] ?? "Sedang halangan / udzur syar'i",
                                    ]
                                );
                            }
                        }
                        $count++;
                    }

                    Notification::make()
                        ->title('Halangan Siswi Berhasil Ditandai')
                        ->body("Berhasil menandai {$count} siswi putri berhalangan sholat. Absensi sholat Dhuhur & Ashar otomatis tercatat 'Sedang Halangan', sedangkan absensi kedatangan tetap tercatat normal.")
                        ->success()
                        ->send();

                    $this->resetTable();
                    $this->dispatch('refresh-presensi-table');
                }),

            // 3. Tombol Khusus Superadmin: Shortcut ke Pengaturan Mesin Fingerspot
            Actions\Action::make('pengaturan_mesin')
                ->label('Integrasi Mesin Fingerspot')
                ->icon('heroicon-o-cpu-chip')
                ->color('warning')
                ->visible(fn () => Auth::user()?->hasRole('superadmin'))
                ->url(fn () => FingerspotDeviceResource::getUrl('index')),
        ];
    }
}
