<?php

namespace App\Filament\Staff\Resources\ProgramKesiswaanResource\Pages;

use App\Filament\Staff\Resources\ProgramKesiswaanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProgramKesiswaans extends ListRecords
{
    protected static string $resource = ProgramKesiswaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->hasAnyRole(['waka_kesiswaan', 'superadmin'])),
        ];
    }
}
