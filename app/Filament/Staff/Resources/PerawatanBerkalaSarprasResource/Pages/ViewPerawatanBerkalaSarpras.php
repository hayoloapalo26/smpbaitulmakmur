<?php

namespace App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource\Pages;

use App\Filament\Staff\Resources\PerawatanBerkalaSarprasResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPerawatanBerkalaSarpras extends ViewRecord
{
    protected static string $resource = PerawatanBerkalaSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Aksi Kepala Sekolah: ACC / Setujui
            Actions\Action::make('setujui')
                ->label('ACC Laporan Perawatan')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $this->record->status_persetujuan !== 'Disetujui')
                ->requiresConfirmation()
                ->modalHeading('ACC Laporan Perawatan Berkala')
                ->modalDescription('Apakah Anda yakin menyetujui dan mengesahkan laporan perawatan sarpras ini?')
                ->modalSubmitActionLabel('Ya, Setujui (ACC)')
                ->form([
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Catatan Disposisi / Tindak Lanjut (Opsional)')
                        ->placeholder('Tuliskan catatan tindak lanjut atau instruksi perawatan ke depan...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_persetujuan' => 'Disetujui',
                        'catatan_kepsek' => !empty($data['catatan_kepsek']) ? $data['catatan_kepsek'] : $this->record->catatan_kepsek,
                        'tanggal_persetujuan' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    Notification::make()
                        ->title('Laporan Perawatan Sarpras Berhasil Disetujui (ACC)')
                        ->success()
                        ->send();
                }),

            // Aksi Kepala Sekolah: Minta Perbaikan / Revisi
            Actions\Action::make('perbaiki')
                ->label('Minta Revisi')
                ->icon('heroicon-m-pencil-square')
                ->color('warning')
                ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && in_array($this->record->status_persetujuan, ['Menunggu Persetujuan', 'Disetujui']))
                ->form([
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Catatan Perbaikan / Arahan')
                        ->required()
                        ->placeholder('Tuliskan poin evaluasi atau pengerjaan yang perlu ditindaklanjuti ulang oleh staf sarpras...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_persetujuan' => 'Perlu Perbaikan',
                        'catatan_kepsek' => $data['catatan_kepsek'],
                        'tanggal_persetujuan' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    Notification::make()
                        ->title('Catatan Perbaikan Terkirim ke Bagian Sarpras')
                        ->warning()
                        ->send();
                }),

            // Aksi Buka Tautan Google Drive Dokumentasi
            Actions\Action::make('buka_drive')
                ->label('Buka Folder Drive')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('info')
                ->url(fn () => $this->record->link_drive_dokumentasi, shouldOpenInNewTab: true)
                ->visible(fn () => !empty($this->record->link_drive_dokumentasi)),

            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'tata_usaha', 'superadmin'])),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
        ];
    }
}
