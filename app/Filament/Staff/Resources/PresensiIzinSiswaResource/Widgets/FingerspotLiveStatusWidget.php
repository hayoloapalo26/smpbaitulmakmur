<?php

namespace App\Filament\Staff\Resources\PresensiIzinSiswaResource\Widgets;

use App\Http\Controllers\Api\FingerspotAttendanceController;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class FingerspotLiveStatusWidget extends Widget
{
    protected static string $view = 'filament.staff.resources.presensi-izin-siswa-resource.widgets.fingerspot-live-status-widget';

    protected int | string | array $columnSpan = 'full';

    public array $diag = [];

    public function mount(): void
    {
        $this->refreshStatus();
    }

    #[On('fingerspot-synced')]
    public function refreshStatus(bool $pullCloud = false): void
    {
        $newLogsCount = 0;
        try {
            $controller = new FingerspotAttendanceController();

            // Jika dipicu secara manual (misal klik "Segarkan Panel & Data"), tarik langsung data terbaru dari cloud
            if ($pullCloud) {
                $config = $controller->getCloudConfig();
                $cloudId = $config['cloud_id'] ?? 'C260B8851F1C1030';
                $pullResult = $controller->pullFromCloudApi($cloudId);
                $newLogsCount = (int) ($pullResult['inserted'] ?? 0);
                $lastActivity = (string) ($pullResult['device_info']['last_activity'] ?? '');
                $machineOffline = empty($lastActivity) || strtolower($lastActivity) === 'n/a';

                $notification = Notification::make()
                    ->title($machineOffline ? '⚠️ Panel Disegarkan (Mesin Belum Online di Cloud)' : '🔄 Panel & Data Presensi Diperbarui')
                    ->body(($pullResult['message'] ?? 'Panel dan data presensi berhasil disegarkan.') . ($newLogsCount > 0 ? " ({$newLogsCount} data scan baru)" : ''))
                    ->duration(6000);

                if ($machineOffline && $newLogsCount === 0) {
                    $notification->warning();
                } else {
                    $notification->success();
                }

                $notification->send();
            }

            $this->diag = $controller->getDiagnosticSnapshot();
        } catch (\Throwable $e) {
            $this->diag = [];
        }

        // Beritahu parent page (ManagePresensiIzinSiswa) untuk merefresh tabel presensi
        $this->dispatch('refresh-presensi-table');
    }
}
