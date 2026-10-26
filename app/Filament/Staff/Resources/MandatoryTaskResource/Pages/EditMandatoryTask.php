<?php

namespace App\Filament\Staff\Resources\MandatoryTaskResource\Pages;

use App\Filament\Staff\Resources\MandatoryTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMandatoryTask extends EditRecord
{
    protected static string $resource = MandatoryTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
