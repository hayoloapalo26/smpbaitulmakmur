<?php

namespace App\Filament\Yayasan\Resources\PermintaanUkgResource\Pages;

use App\Filament\Yayasan\Resources\PermintaanUkgResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUkgReportCard extends ViewRecord
{
    protected static string $resource = PermintaanUkgResource::class;
    protected static string $view = 'filament.yayasan.pages.view-ukg-report-card';

    public function getTitle(): string
    {
        return 'Raport PKG: ' . ($this->record->teacher->name ?? 'Guru');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cetak_pdf')
                ->label('Download Raport PKG PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->url(fn () => route('ukg.report-card.pdf', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('print')
                ->label('Cetak / Print')
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->url(fn () => route('ukg.report-card.print', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('edit')
                ->label('Edit / Nilai')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->url(fn () => PermintaanUkgResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'ukg' => $this->record,
        ];
    }
}
