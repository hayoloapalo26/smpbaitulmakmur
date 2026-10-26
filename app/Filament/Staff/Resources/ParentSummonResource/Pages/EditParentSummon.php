<?php

namespace App\Filament\Staff\Resources\ParentSummonResource\Pages;

use App\Filament\Staff\Resources\ParentSummonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParentSummon extends EditRecord
{
    protected static string $resource = ParentSummonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
