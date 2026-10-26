<?php

namespace App\Filament\Staff\Resources\ReadingQualityScoreResource\Pages;

use App\Filament\Staff\Resources\ReadingQualityScoreResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageReadingQualityScores extends ManageRecords
{
    protected static string $resource = ReadingQualityScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
