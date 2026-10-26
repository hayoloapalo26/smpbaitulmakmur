<?php

namespace App\Filament\Yayasan\Resources\LibraryAssessmentResource\Pages;

use App\Filament\Yayasan\Resources\LibraryAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLibraryAssessment extends EditRecord
{
    protected static string $resource = LibraryAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
