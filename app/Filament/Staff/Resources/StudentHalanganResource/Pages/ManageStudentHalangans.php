<?php

namespace App\Filament\Staff\Resources\StudentHalanganResource\Pages;

use App\Filament\Staff\Resources\StudentHalanganResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentHalangans extends ManageRecords
{
    protected static string $resource = StudentHalanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('+ Catat Halangan Siswi'),
        ];
    }
}
