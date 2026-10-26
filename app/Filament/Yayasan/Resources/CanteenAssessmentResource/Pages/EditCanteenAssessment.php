<?php

namespace App\Filament\Yayasan\Resources\CanteenAssessmentResource\Pages;

use App\Filament\Yayasan\Resources\CanteenAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCanteenAssessment extends EditRecord
{
    protected static string $resource = CanteenAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
