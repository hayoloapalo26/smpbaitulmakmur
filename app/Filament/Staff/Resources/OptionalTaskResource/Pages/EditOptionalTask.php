<?php

namespace App\Filament\Staff\Resources\OptionalTaskResource\Pages;

use App\Filament\Staff\Resources\OptionalTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOptionalTask extends EditRecord
{
    protected static string $resource = OptionalTaskResource::class;

    protected static ?string $title = 'Edit Tugas Insidentil';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
