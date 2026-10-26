<?php

namespace App\Filament\Yayasan\Resources\SchoolVisionMissionResource\Pages;

use App\Filament\Yayasan\Resources\SchoolVisionMissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSchoolVisionMissions extends ManageRecords
{
    protected static string $resource = SchoolVisionMissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Visi & Misi Lembaga')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
