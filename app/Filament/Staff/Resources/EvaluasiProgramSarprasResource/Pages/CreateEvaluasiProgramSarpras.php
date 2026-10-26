<?php

namespace App\Filament\Staff\Resources\EvaluasiProgramSarprasResource\Pages;

use App\Filament\Staff\Resources\EvaluasiProgramSarprasResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvaluasiProgramSarpras extends CreateRecord
{
    protected static string $resource = EvaluasiProgramSarprasResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        if (auth()->check() && !empty(auth()->user()->institution_id)) {
            $data['institution_id'] = auth()->user()->institution_id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
