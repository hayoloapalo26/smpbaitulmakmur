<?php

namespace App\Filament\Staff\Resources\HubunganInstitusiResource\Pages;

use App\Filament\Staff\Resources\HubunganInstitusiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHubunganInstitusis extends ListRecords
{
    protected static string $resource = HubunganInstitusiResource::class;

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
