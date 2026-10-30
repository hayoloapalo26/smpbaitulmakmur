<?php

namespace App\Filament\Staff\Resources\PenempatanSarprasResource\Pages;

use App\Filament\Staff\Resources\PenempatanSarprasResource;
use App\Filament\Staff\Resources\PenempatanSarprasResource\Widgets\PenempatanSarprasStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenempatanSarpras extends ListRecords
{
    protected static string $resource = PenempatanSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Catat Penempatan Ruangan')
                ->icon('heroicon-m-plus')
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PenempatanSarprasStatsWidget::class,
        ];
    }
}
