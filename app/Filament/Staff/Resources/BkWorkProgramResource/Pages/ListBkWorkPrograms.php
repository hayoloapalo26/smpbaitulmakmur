<?php

namespace App\Filament\Staff\Resources\BkWorkProgramResource\Pages;

use App\Filament\Staff\Resources\BkWorkProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBkWorkPrograms extends ListRecords
{
    protected static string $resource = BkWorkProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->hasAnyRole(['bk', 'superadmin'])),
        ];
    }
}
