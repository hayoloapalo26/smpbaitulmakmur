<?php

namespace App\Filament\Yayasan\Resources\LabAssessmentResource\Pages;

use App\Filament\Yayasan\Resources\LabAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLabAssessments extends ListRecords
{
    protected static string $resource = LabAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
