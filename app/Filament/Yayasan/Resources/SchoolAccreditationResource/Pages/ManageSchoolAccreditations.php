<?php

namespace App\Filament\Yayasan\Resources\SchoolAccreditationResource\Pages;

use App\Filament\Yayasan\Resources\SchoolAccreditationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSchoolAccreditations extends ManageRecords
{
    protected static string $resource = SchoolAccreditationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Akreditasi Lembaga')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
