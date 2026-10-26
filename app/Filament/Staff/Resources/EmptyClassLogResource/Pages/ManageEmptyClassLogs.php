<?php

namespace App\Filament\Staff\Resources\EmptyClassLogResource\Pages;

use App\Filament\Staff\Resources\EmptyClassLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageEmptyClassLogs extends ManageRecords
{
    protected static string $resource = EmptyClassLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
