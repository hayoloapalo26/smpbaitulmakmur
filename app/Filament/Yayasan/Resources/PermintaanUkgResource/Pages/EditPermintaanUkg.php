<?php

namespace App\Filament\Yayasan\Resources\PermintaanUkgResource\Pages;

use App\Filament\Yayasan\Resources\PermintaanUkgResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPermintaanUkg extends EditRecord
{
    protected static string $resource = PermintaanUkgResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('report_card')
                ->label('Lihat Raport PKG')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->url(fn () => PermintaanUkgResource::getUrl('view', ['record' => $this->record])),
            Actions\Action::make('cetak_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->url(fn () => route('ukg.report-card.pdf', $this->record))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
