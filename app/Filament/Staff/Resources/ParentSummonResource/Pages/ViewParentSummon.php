<?php

namespace App\Filament\Staff\Resources\ParentSummonResource\Pages;

use App\Filament\Staff\Resources\ParentSummonResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewParentSummon extends ViewRecord
{
    protected static string $resource = ParentSummonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => !auth()->user()?->hasRole('kepala_sekolah') && ParentSummonResource::canEdit($this->record)),
        ];
    }
}
