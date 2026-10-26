<?php

namespace App\Filament\Staff\Resources\DutySwapRequestResource\Pages;

use App\Filament\Staff\Resources\DutySwapRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDutySwapRequests extends ManageRecords
{
    protected static string $resource = DutySwapRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
