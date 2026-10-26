<?php

namespace App\Filament\Staff\Resources\HumasProgramResource\Pages;

use App\Filament\Staff\Resources\HumasProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageHumasPrograms extends ManageRecords
{
    protected static string $resource = HumasProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
