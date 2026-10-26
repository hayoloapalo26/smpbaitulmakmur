<?php

namespace App\Filament\Staff\Resources\EvaluasiProgramKerjaResource\Pages;

use App\Filament\Staff\Resources\EvaluasiProgramKerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvaluasiProgramKerja extends EditRecord
{
    protected static string $resource = EvaluasiProgramKerjaResource::class;

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
