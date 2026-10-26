<?php

namespace App\Filament\Staff\Resources\HarmonisasiInternalResource\Pages;

use App\Filament\Staff\Resources\HarmonisasiInternalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHarmonisasiInternals extends ListRecords
{
    protected static string $resource = HarmonisasiInternalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kegiatan Harmonisasi')
                ->icon('heroicon-m-plus')
                ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
        ];
    }
}
