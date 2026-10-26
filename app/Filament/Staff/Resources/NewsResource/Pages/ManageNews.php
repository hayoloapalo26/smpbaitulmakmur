<?php

namespace App\Filament\Staff\Resources\NewsResource\Pages;

use App\Filament\Staff\Resources\NewsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageNews extends ManageRecords
{
    protected static string $resource = NewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
