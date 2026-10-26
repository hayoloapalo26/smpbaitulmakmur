<?php

namespace App\Filament\Staff\Resources\DutyScheduleResource\Pages;

use App\Filament\Staff\Resources\DutyScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDutySchedules extends ListRecords
{
    protected static string $resource = DutyScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('tukar_jadwal')
                ->label('Pengajuan Tukar Jadwal Piket')
                ->icon('heroicon-o-arrows-right-left')
                ->color('info')
                ->url(fn () => \App\Filament\Staff\Resources\DutySwapRequestResource::getUrl()),
            Actions\CreateAction::make(),
        ];
    }
}
