<?php

namespace App\Filament\Staff\Resources\HubunganMasyarakatResource\Pages;

use App\Filament\Staff\Resources\HubunganMasyarakatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHubunganMasyarakat extends EditRecord
{
    protected static string $resource = HubunganMasyarakatResource::class;

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
