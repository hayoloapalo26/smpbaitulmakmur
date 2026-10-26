<?php

namespace App\Filament\Staff\Resources\HubunganMasyarakatResource\Pages;

use App\Filament\Staff\Resources\HubunganMasyarakatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHubunganMasyarakats extends ListRecords
{
    protected static string $resource = HubunganMasyarakatResource::class;

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
