<?php

namespace App\Filament\Staff\Resources\DcmResponseResource\Pages;

use App\Filament\Staff\Resources\DcmResponseResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListDcmResponses extends ListRecords
{
    protected static string $resource = DcmResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analisis')
                ->label('Analisis Penskoran DCM')
                ->icon('heroicon-o-chart-bar')
                ->color('purple')
                ->url(fn () => route('filament.staff.pages.dcm-analysis')),
        ];
    }
}
