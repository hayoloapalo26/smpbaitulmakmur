<?php

namespace App\Filament\Staff\Resources\OptionalTaskResource\Pages;

use App\Filament\Staff\Resources\OptionalTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOptionalTask extends EditRecord
{
    protected static string $resource = OptionalTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
