<?php

namespace App\Filament\Staff\Resources\DailyAttendanceResource\Pages;

use App\Filament\Staff\Resources\DailyAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDailyAttendances extends ManageRecords
{
    protected static string $resource = DailyAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->disabled(fn () => now()->format('H:i') > '09:00' && !auth()->user()->hasRole('kepala_sekolah'))
                ->tooltip(fn () => (now()->format('H:i') > '09:00' && !auth()->user()->hasRole('kepala_sekolah')) ? 'Batas waktu presensi pagi (09:00) telah lewat' : null),
        ];
    }
}
