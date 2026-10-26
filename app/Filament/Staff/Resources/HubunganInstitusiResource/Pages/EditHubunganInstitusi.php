<?php

namespace App\Filament\Staff\Resources\HubunganInstitusiResource\Pages;

use App\Filament\Staff\Resources\HubunganInstitusiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHubunganInstitusi extends EditRecord
{
    protected static string $resource = HubunganInstitusiResource::class;

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
