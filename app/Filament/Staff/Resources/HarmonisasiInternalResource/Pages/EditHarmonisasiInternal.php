<?php

namespace App\Filament\Staff\Resources\HarmonisasiInternalResource\Pages;

use App\Filament\Staff\Resources\HarmonisasiInternalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHarmonisasiInternal extends EditRecord
{
    protected static string $resource = HarmonisasiInternalResource::class;

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
