<?php

namespace App\Filament\Staff\Resources\StudentCaseResource\Pages;

use App\Filament\Staff\Resources\StudentCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentCase extends CreateRecord
{
    protected static string $resource = StudentCaseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['wali_kelas_id'] = auth()->id();
        $data['ditangani_oleh'] = 'wali_kelas';
        $data['status'] = 'proses';
        return $data;
    }
}
