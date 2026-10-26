<?php

namespace App\Filament\Yayasan\Resources\PermintaanSupervisiResource\Pages;

use App\Filament\Yayasan\Resources\PermintaanSupervisiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPermintaanSupervisi extends EditRecord
{
    protected static string $resource = PermintaanSupervisiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('report_card')
                ->label('Lihat Report Card')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->url(fn () => PermintaanSupervisiResource::getUrl('view', ['record' => $this->record])),
            Actions\Action::make('cetak_pdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->url(fn () => route('supervisi.report-card.pdf', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
