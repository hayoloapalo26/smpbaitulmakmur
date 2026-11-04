<?php

namespace App\Filament\Staff\Resources\ExtracurricularGradeResource\Pages;

use App\Filament\Staff\Resources\ExtracurricularGradeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExtracurricularGrade extends CreateRecord
{
    protected static string $resource = ExtracurricularGradeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if (empty($data['pembina_id'])) {
            $data['pembina_id'] = $user?->id;
        }
        if (empty($data['institution_id'])) {
            $data['institution_id'] = $user?->institution_id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
