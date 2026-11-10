<?php

namespace App\Filament\Staff\Resources\PresensiIzinSiswaResource\Widgets;

use App\Http\Controllers\Api\FingerspotAttendanceController;
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
    public function refreshStatus(): void
    {
        try {
            $controller = new FingerspotAttendanceController();
            $this->diag = $controller->getDiagnosticSnapshot();
        } catch (\Throwable $e) {
            $this->diag = [];
        }
    }
}
