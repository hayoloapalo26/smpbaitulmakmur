<?php

namespace App\Filament\Staff\Resources\PresensiIzinSiswaResource\Pages;

use App\Filament\Staff\Resources\PresensiIzinSiswaResource;
use App\Filament\Staff\Resources\PresensiIzinSiswaResource\Widgets\FingerspotLiveStatusWidget;
use App\Http\Controllers\Api\FingerspotAttendanceController;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ManagePresensiIzinSiswa extends ManageRecords
{
    protected static string $resource = PresensiIzinSiswaResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            FingerspotLiveStatusWidget::class,
        ];
    }

    public function mount(): void
    {
        parent::mount();

        // Jika Cloud ID mesin sudah dikonfigurasi, otomatis tarik data Cloud secara berkala (jeda 45 detik)
        // sebagai backup bila webhook cloud sedang tertunda
        try {
            $controller = new FingerspotAttendanceController();
            $config = $controller->getCloudConfig();

            if (!empty($config['cloud_id']) && Cache::add('fingerspot_ui_autosync_cooldown', true, 45)) {
                $controller->pullFromCloudApi($config['cloud_id']);
            }
        } catch (\Throwable $e) {
            // Jangan ganggu tampilan halaman jika koneksi cloud timeout
        }
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Presensi'),
            'masuk' => Tab::make('Kehadiran Harian')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipe_absen', 'masuk')),
            'sholat' => Tab::make('Absensi Sholat')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('tipe_absen', ['sholat_dhuha', 'sholat_dhuhur', 'sholat_ashar'])),
            'pulang' => Tab::make('🏠 Ashar & Pulang')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('tipe_absen', ['sholat_ashar', 'pulang'])),
            'terlambat' => Tab::make('⏰ Terlambat')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_late', true)),
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
            Actions\Action::make('sync_cloud_fingerspot')
                ->label('🔄 Sinkron Mesin Fingerspot')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->modalHeading('🔄 Sinkronisasi Langsung Cloud developer.fingerspot.io')
                ->modalDescription('Tarik paksa log scan sidik jari langsung dari server Cloud Fingerspot Revo W230N (API Key terhubung). Masukkan Cloud ID mesin Anda untuk menyimpan konfigurasi permanen.')
                ->modalSubmitActionLabel('Tarik Log Sekarang')
                ->form(function () {
                    $controller = new FingerspotAttendanceController();
                    $config = $controller->getCloudConfig();
                    $today = Carbon::now('Asia/Jakarta')->toDateString();

                    return [
                        Forms\Components\Placeholder::make('info_endpoint')
                            ->label('URL Webhook Aktif & Aturan Jam Presensi')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="text-xs space-y-1 text-gray-600 dark:text-gray-300">'
                                . '<div><strong>Webhook URL:</strong> <code>' . url('/api/fingerspot/attendance') . '</code> (API Key: Aktif)</div>'
                                . '<div><strong>Masuk Pagi:</strong> 06.00 – 06.50 | <strong>Dhuhur:</strong> 12.00 – 12.15 | <strong>Ashar & Pulang:</strong> 15.00 – 15.15</div>'
                                . '<div><strong>Penguncian:</strong> Scan pertama dikunci per waktu; lewat batas waktu otomatis ditandai <strong>Terlambat</strong>.</div>'
                                . '</div>'
                            )),
                        Forms\Components\TextInput::make('cloud_id')
                            ->label('Cloud ID Mesin Revo W230N')
                            ->placeholder('Contoh: C2630450C31xxxxx')
                            ->helperText('Lihat kode Cloud ID di web developer.fingerspot.io pada daftar device/mesin Anda. Otomatis tersimpan setelah disubmit.')
                            ->default($config['cloud_id'] ?? '')
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->default($today)
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->default($today)
                            ->required(),
                    ];
                })
                ->action(function (array $data) {
                    $controller = new FingerspotAttendanceController();
                    $start = Carbon::parse($data['start_date'])->toDateString();
                    $end = Carbon::parse($data['end_date'])->toDateString();

                    $result = $controller->pullFromCloudApi(trim($data['cloud_id']), $start, $end);

                    if ($result['success'] ?? false) {
                        $unmatchedText = '';
                        if (!empty($result['unmatched_pins'])) {
                            $pins = implode(', ', $result['unmatched_pins']);
                            $unmatchedText = "<br><strong>⚠️ PIN belum terdaftar di Data Siswa/Guru:</strong> {$pins}";
                        }
                        if (!empty($result['device_info']['webhook_url'])) {
                            $unmatchedText .= "<br><strong>Webhook URL terpasang di Cloud:</strong> <code>{$result['device_info']['webhook_url']}</code>";
                        }

                        Notification::make()
                            ->title('✅ Sinkronisasi Fingerspot Cloud Berhasil!')
                            ->body(($result['message'] ?? 'Selesai') . $unmatchedText)
                            ->success()
                            ->duration(10000)
                            ->send();
                    } else {
                        Notification::make()
                            ->title('❌ Gagal Menarik Data Cloud')
                            ->body($result['message'] ?? 'Periksa kembali Cloud ID Anda di developer.fingerspot.io.')
                            ->danger()
                            ->duration(8000)
                            ->send();
                    }

                    $this->dispatch('fingerspot-synced');
                }),

            Actions\Action::make('tes_fingerspot')
                ->label('⚡ Tes Scan Fingerspot')
                ->icon('heroicon-o-finger-print')
                ->color('success')
                ->modalHeading('⚡ Simulasi & Tes Integrasi Mesin Fingerspot Revo W230N')
                ->modalDescription('Uji ketepatan waktu (Masuk 06.00-06.50, Dhuhur 12.00-12.15, Ashar & Pulang 15.00-15.15) dan penguncian 1x scan langsung ke tabel Presensi & Izin Harian Siswa.')
                ->modalSubmitActionLabel('Kirim Log Presensi')
                ->form([
                    Forms\Components\Select::make('student_id')
                        ->label('Pilih Siswa yang Dites')
                        ->options(function () {
                            return \App\Models\Student::withoutGlobalScopes()
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($s) => [
                                    $s->id => "{$s->name} (" . ($s->classroom?->name ?? 'Tanpa Rombel') . " | ID Finger: " . ($s->id_finger ?? $s->fingerspot_pin ?? 'Belum diset') . " | NIS: {$s->nis})"
                                ]);
                        })
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('tipe_absen')
                        ->label('Sesi / Kategori Simulasi')
                        ->options([
                            'auto'          => '⏱️ Otomatis Sesuai Jam Scan',
                            'masuk'         => '🌅 Masuk Pagi (Batas 06:50)',
                            'sholat_dhuhur' => '🕌 Sholat Dhuhur (Batas 12:15)',
                            'sholat_ashar'  => '🏠 Sholat Ashar & Pulang (Batas 15:15)',
                        ])
                        ->default('auto')
                        ->required(),
                    Forms\Components\TimePicker::make('waktu_scan')
                        ->label('Jam Scan Simulasi (Kosongkan untuk jam sekarang)')
                        ->seconds(false),
                ])
                ->action(function (array $data) {
                    $student = \App\Models\Student::withoutGlobalScopes()->find($data['student_id']);
                    if (!$student) {
                        Notification::make()
                            ->title('Siswa tidak ditemukan')
                            ->danger()
                            ->send();
                        return;
                    }

                    $now = now()->setTimezone('Asia/Jakarta');
                    if (!empty($data['waktu_scan'])) {
                        try {
                            $parts = explode(':', $data['waktu_scan']);
                            $now->setHour((int) $parts[0])->setMinute((int) $parts[1])->setSecond(0);
                        } catch (\Throwable $e) {}
                    }

                    $tipe = $data['tipe_absen'] === 'auto' ? null : $data['tipe_absen'];
                    $pin = $student->id_finger ?: ($student->fingerspot_pin ?: $student->nis);

                    $controller = new FingerspotAttendanceController();
                    $result = $controller->processAttendanceRecord(
                        (string) $pin,
                        $now->toDateTimeString(),
                        1,
                        0,
                        $tipe
                    );

                    if (($result['status'] ?? '') === 'success' || ($result['action'] ?? '') === 'halangan') {
                        $statusBadge = ($result['is_late'] ?? false) ? '⏰ TERLAMBAT' : '✅ TEPAT WAKTU';
                        Notification::make()
                            ->title("✅ Scan Berhasil ({$statusBadge})")
                            ->body($result['keterangan'] ?? "Presensi <b>{$student->name}</b> tercatat.")
                            ->success()
                            ->send();
                    } elseif (($result['status'] ?? '') === 'duplicate') {
                        Notification::make()
                            ->title('🔒 Absen Terkunci (Scan Ke-2 Ditolak)')
                            ->body($result['message'] ?? 'Siswa sudah tercatat pada sesi ini hari ini.')
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Status: ' . ($result['status'] ?? 'Info'))
                            ->body($result['message'] ?? 'Pengecekan selesai')
                            ->info()
                            ->send();
                    }

                    $this->dispatch('fingerspot-synced');
                }),

            Actions\Action::make('rekap_lengkap_siswa')
                ->label('Rekap Total Absensi & Sholat Siswa')
                ->icon('heroicon-o-chart-bar-square')
                ->color('warning')
                ->url(fn () => \App\Filament\Staff\Pages\PrayerAttendanceReport::getUrl())
                ->visible(fn () => auth()->user()->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'wali_kelas', 'guru_kelas', 'superadmin'])),
            Actions\Action::make('absensi_kolektif')
                ->label('Input Presensi Kolektif Per Kelas')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('info')
                ->url(fn () => \App\Filament\Staff\Pages\AbsensiManual::getUrl())
                ->visible(fn () => auth()->user()->hasAnyRole(['guru_piket', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin'])),
            Actions\CreateAction::make()
                ->label('Catat Hadir / Izin / Sakit / Alpha')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
