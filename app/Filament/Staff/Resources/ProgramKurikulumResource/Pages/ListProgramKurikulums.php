<?php

namespace App\Filament\Staff\Resources\ProgramKurikulumResource\Pages;

use App\Filament\Staff\Resources\ProgramKurikulumResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProgramKurikulums extends ListRecords
{
    protected static string $resource = ProgramKurikulumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->hasAnyRole(['waka_kurikulum', 'superadmin'])),
        ];
    }
}
