<?php

namespace App\Filament\Staff\Resources\AcademicYearResource\Pages;

use App\Filament\Staff\Resources\AcademicYearResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAcademicYears extends ManageRecords
{
    protected static string $resource = AcademicYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
