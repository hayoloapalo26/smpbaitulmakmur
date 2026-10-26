<?php

namespace App\Filament\Staff\Resources\HubunganWaliMuridResource\Pages;

use App\Filament\Staff\Resources\HubunganWaliMuridResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHubunganWaliMurid extends EditRecord
{
    protected static string $resource = HubunganWaliMuridResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
