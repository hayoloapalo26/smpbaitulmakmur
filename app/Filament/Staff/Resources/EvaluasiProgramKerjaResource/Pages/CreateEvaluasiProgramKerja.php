<?php

namespace App\Filament\Staff\Resources\EvaluasiProgramKerjaResource\Pages;

use App\Filament\Staff\Resources\EvaluasiProgramKerjaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvaluasiProgramKerja extends CreateRecord
{
    protected static string $resource = EvaluasiProgramKerjaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        if (auth()->user()?->institution_id) {
            $data['institution_id'] = auth()->user()->institution_id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
