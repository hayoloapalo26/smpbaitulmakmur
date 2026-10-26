<?php

namespace App\Filament\Staff\Resources\PrayerAttendanceResource\Pages;

use App\Filament\Staff\Resources\PrayerAttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePrayerAttendances extends ManageRecords
{
    protected static string $resource = PrayerAttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('rekap_ibadah')
                ->label('Rekap Pemantauan Ibadah')
                ->icon('heroicon-o-chart-bar')
                ->color('info')
                ->url(fn () => \App\Filament\Staff\Pages\PrayerAttendanceReport::getUrl()),
            Actions\Action::make('download_pdf')
                ->label('Unduh PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('report.prayer'))
                ->openUrlInNewTab(),
            Actions\CreateAction::make(),
        ];
    }
}
