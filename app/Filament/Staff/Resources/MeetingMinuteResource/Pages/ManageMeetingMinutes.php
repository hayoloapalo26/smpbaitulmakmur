<?php

namespace App\Filament\Staff\Resources\MeetingMinuteResource\Pages;

use App\Filament\Staff\Resources\MeetingMinuteResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMeetingMinutes extends ManageRecords
{
    protected static string $resource = MeetingMinuteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
