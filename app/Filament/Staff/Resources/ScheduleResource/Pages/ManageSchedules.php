<?php

namespace App\Filament\Staff\Resources\ScheduleResource\Pages;

use App\Filament\Staff\Resources\ScheduleResource;
use App\Filament\Staff\Resources\TeacherSubjectClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSchedules extends ManageRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('routing_guru_mapel')
                ->label('Routing Guru Mapel')
                ->icon('heroicon-o-link')
                ->color('info')
                ->visible(fn () => auth()->user()?->hasAnyRole(['waka_kurikulum', 'superadmin']) ?? false)
                ->url(fn () => TeacherSubjectClassResource::getUrl()),
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['waka_kurikulum', 'tata_usaha', 'kepala_sekolah', 'superadmin']) ?? false),
        ];
    }
}
