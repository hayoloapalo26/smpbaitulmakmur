<?php

namespace App\Filament\Yayasan\Resources\CanteenAssessmentResource\Pages;

use App\Filament\Yayasan\Resources\CanteenAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCanteenAssessments extends ListRecords
{
    protected static string $resource = CanteenAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
