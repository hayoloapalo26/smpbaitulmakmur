<?php

namespace App\Filament\Staff\Resources\LetterResource\Pages;

use App\Filament\Staff\Resources\LetterResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLetters extends ManageRecords
{
    protected static string $resource = LetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
