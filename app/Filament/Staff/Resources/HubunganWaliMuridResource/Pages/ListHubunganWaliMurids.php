<?php

namespace App\Filament\Staff\Resources\HubunganWaliMuridResource\Pages;

use App\Filament\Staff\Resources\HubunganWaliMuridResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHubunganWaliMurids extends ListRecords
{
    protected static string $resource = HubunganWaliMuridResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kegiatan')
                ->icon('heroicon-m-plus')
                ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
        ];
    }
}
