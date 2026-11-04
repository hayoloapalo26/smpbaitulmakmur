<?php

namespace App\Filament\Staff\Resources\ExtracurricularGradeResource\Pages;

use App\Filament\Staff\Resources\ExtracurricularGradeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExtracurricularGrade extends EditRecord
{
    protected static string $resource = ExtracurricularGradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasRole('superadmin')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
