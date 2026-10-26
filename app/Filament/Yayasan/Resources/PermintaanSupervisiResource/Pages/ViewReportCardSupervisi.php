<?php

namespace App\Filament\Yayasan\Resources\PermintaanSupervisiResource\Pages;

use App\Filament\Yayasan\Resources\PermintaanSupervisiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReportCardSupervisi extends ViewRecord
{
    protected static string $resource = PermintaanSupervisiResource::class;
    protected static string $view = 'filament.yayasan.pages.view-report-card';

    public function getTitle(): string
    {
        return 'Report Card Supervisi: ' . ($this->record->teacher->name ?? 'Guru');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cetak_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->url(fn () => route('supervisi.report-card.pdf', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('print')
                ->label('Cetak / Print')
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->url(fn () => route('supervisi.report-card.print', $this->record))
                ->openUrlInNewTab(),
            Actions\Action::make('edit')
                ->label('Edit / Nilai')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->url(fn () => PermintaanSupervisiResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
            'supervision' => $this->record,
        ];
    }
}
