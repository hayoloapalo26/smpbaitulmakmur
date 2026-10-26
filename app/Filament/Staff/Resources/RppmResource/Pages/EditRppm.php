<?php

namespace App\Filament\Staff\Resources\RppmResource\Pages;

use App\Filament\Staff\Resources\RppmResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRppm extends EditRecord
{
    protected static string $resource = RppmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
