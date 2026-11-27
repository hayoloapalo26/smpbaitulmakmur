<?php

namespace App\Filament\Staff\Resources\GuardianResource\Pages;

use App\Filament\Staff\Resources\GuardianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuardians extends ListRecords
{
    protected static string $resource = GuardianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data Orang Tua'),
        ];
    }
}
