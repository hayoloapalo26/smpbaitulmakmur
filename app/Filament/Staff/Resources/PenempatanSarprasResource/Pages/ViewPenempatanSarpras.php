<?php

namespace App\Filament\Staff\Resources\PenempatanSarprasResource\Pages;

use App\Filament\Staff\Resources\PenempatanSarprasResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPenempatanSarpras extends ViewRecord
{
    protected static string $resource = PenempatanSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
        ];
    }
}
