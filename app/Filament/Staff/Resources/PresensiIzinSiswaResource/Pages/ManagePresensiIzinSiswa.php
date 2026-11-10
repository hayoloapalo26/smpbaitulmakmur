<?php

namespace App\Filament\Staff\Resources\PresensiIzinSiswaResource\Pages;

use App\Filament\Staff\Resources\PresensiIzinSiswaResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
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
            Actions\Action::make('tes_fingerspot')
                ->label('⚡ Tes Scan Fingerspot')
                ->icon('heroicon-o-finger-print')
                ->color('success')
                ->modalHeading('⚡ Simulasi & Tes Integrasi Mesin Fingerspot Revo W230N')
                ->modalDescription('Gunakan fitur ini untuk memverifikasi bahwa rekaman presensi (baik Masuk maupun Sholat) langsung masuk ke sistem dan tersinkronisasi sempurna.')
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
                        ->label('Tipe Presensi yang Disimulasikan')
                        ->options([
                            'auto'          => '⏱️ Otomatis Sesuai Jam Saat Ini',
                            'masuk'         => '🌅 Masuk Pagi',
                            'sholat_dhuhur' => '🕌 Sholat Dhuhur Berjamaah',
                            'sholat_ashar'  => '🕌 Sholat Ashar Berjamaah',
                            'pulang'        => '🏠 Kepulangan',
                        ])
                        ->default('auto')
                        ->required(),
                    Forms\Components\TimePicker::make('waktu_scan')
                        ->label('Waktu Scan (Opsional, kosongkan untuk jam sekarang)')
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
                            $now->setHour((int) $parts[0])->setMinute((int) $parts[1]);
                        } catch (\Throwable $e) {}
                    }

                    $tipe = $data['tipe_absen'] === 'auto' ? null : $data['tipe_absen'];
                    $pin = $student->id_finger ?: ($student->fingerspot_pin ?: $student->nis);

                    $controller = new \App\Http\Controllers\Api\FingerspotAttendanceController();
                    $result = $controller->processAttendanceRecord(
                        (string) $pin,
                        $now->toDateTimeString(),
                        1,
                        0,
                        $tipe
                    );

                    if (($result['status'] ?? '') === 'success' || ($result['action'] ?? '') === 'halangan') {
                        Notification::make()
                            ->title('✅ Scan Fingerspot Berhasil!')
                            ->body("Presensi atas nama <b>{$student->name}</b> untuk <b>" . strtoupper(str_replace('_', ' ', $result['tipe'] ?? 'masuk')) . "</b> berhasil dicatat ke sistem.")
                            ->success()
                            ->send();
                    } elseif (($result['status'] ?? '') === 'duplicate') {
                        Notification::make()
                            ->title('ℹ️ Sudah Tercatat')
                            ->body($result['message'] ?? 'Presensi siswa sudah tercatat sebelumnya.')
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Status: ' . ($result['status'] ?? 'Info'))
                            ->body($result['message'] ?? 'Pengecekan selesai')
                            ->info()
                            ->send();
                    }
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
