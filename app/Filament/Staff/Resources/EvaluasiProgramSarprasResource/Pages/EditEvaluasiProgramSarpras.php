<?php

namespace App\Filament\Staff\Resources\EvaluasiProgramSarprasResource\Pages;

use App\Filament\Staff\Resources\EvaluasiProgramSarprasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvaluasiProgramSarpras extends EditRecord
{
    protected static string $resource = EvaluasiProgramSarprasResource::class;

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
