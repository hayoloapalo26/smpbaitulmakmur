<?php

namespace App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource\Pages;

use App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource;
use App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource\Widgets\PerbaikanPenggantianStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPerbaikanPenggantianSarpras extends ListRecords
{
    protected static string $resource = PerbaikanPenggantianSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Lapor Kerusakan / Penggantian')
                ->icon('heroicon-m-plus')
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PerbaikanPenggantianStatsWidget::class,
        ];
    }
}
