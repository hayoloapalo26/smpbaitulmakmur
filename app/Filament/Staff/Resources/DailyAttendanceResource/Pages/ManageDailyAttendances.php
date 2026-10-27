<?php

namespace App\Filament\Staff\Resources\DailyAttendanceResource\Pages;

use App\Filament\Staff\Resources\DailyAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManageDailyAttendances extends ManageRecords
{
    protected static string $resource = DailyAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Catat Presensi Siswa')
                ->icon('heroicon-o-plus'),
        ];
    }
}
