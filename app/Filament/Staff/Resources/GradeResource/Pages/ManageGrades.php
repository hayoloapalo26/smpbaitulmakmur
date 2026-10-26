<?php

namespace App\Filament\Staff\Resources\GradeResource\Pages;

use App\Filament\Staff\Resources\GradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageGrades extends ManageRecords
{
    protected static string $resource = GradeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
