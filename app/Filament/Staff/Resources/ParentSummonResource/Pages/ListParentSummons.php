<?php

namespace App\Filament\Staff\Resources\ParentSummonResource\Pages;

use App\Filament\Staff\Resources\ParentSummonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParentSummons extends ListRecords
{
    protected static string $resource = ParentSummonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => !auth()->user()?->hasRole('kepala_sekolah')),
        ];
    }
}
