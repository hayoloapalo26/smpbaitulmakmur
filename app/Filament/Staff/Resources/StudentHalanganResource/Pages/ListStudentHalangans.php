<?php

namespace App\Filament\Staff\Resources\StudentHalanganResource\Pages;

use App\Filament\Staff\Resources\StudentHalanganResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentHalangans extends ListRecords
{
    protected static string $resource = StudentHalanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
