<?php

namespace App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource\Pages;

use App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPerbaikanPenggantianSarpras extends EditRecord
{
    protected static string $resource = PerbaikanPenggantianSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
