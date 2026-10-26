<?php

namespace App\Filament\Staff\Resources\DocumentArchiveResource\Pages;

use App\Filament\Staff\Resources\DocumentArchiveResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDocumentArchives extends ManageRecords
{
    protected static string $resource = DocumentArchiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
