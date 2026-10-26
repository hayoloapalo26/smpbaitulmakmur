<?php

namespace App\Filament\Staff\Resources\HubunganWaliMuridResource\Pages;

use App\Filament\Staff\Resources\HubunganWaliMuridResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewHubunganWaliMurid extends ViewRecord
{
    protected static string $resource = HubunganWaliMuridResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Aksi Kepala Sekolah: ACC / Setujui
            Actions\Action::make('setujui')
                ->label('ACC Kegiatan')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $this->record->status_persetujuan !== 'Disetujui')
                ->requiresConfirmation()
                ->modalHeading('ACC Kegiatan Hubungan dengan Wali Murid')
                ->modalDescription('Apakah Anda yakin menyetujui kegiatan bersama wali murid ini?')
                ->modalSubmitActionLabel('Ya, Setujui')
                ->form([
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Catatan Tambahan (Opsional)')
                        ->placeholder('Tuliskan catatan atau instruksi jika ada...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_persetujuan' => 'Disetujui',
                        'catatan_kepsek' => !empty($data['catatan_kepsek']) ? $data['catatan_kepsek'] : $this->record->catatan_kepsek,
                        'tanggal_persetujuan' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    Notification::make()
                        ->title('Kegiatan Wali Murid Disetujui (ACC)')
                        ->success()
                        ->send();
                }),

            // Aksi Kepala Sekolah: Minta Perbaikan
            Actions\Action::make('perbaiki')
                ->label('Minta Revisi')
                ->icon('heroicon-m-pencil-square')
                ->color('warning')
                ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && in_array($this->record->status_persetujuan, ['Menunggu Persetujuan', 'Disetujui']))
                ->form([
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Catatan Perbaikan / Revisi')
                        ->required()
                        ->placeholder('Tuliskan poin-poin yang perlu diperbaiki oleh Humas...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_persetujuan' => 'Perlu Perbaikan',
                        'catatan_kepsek' => $data['catatan_kepsek'],
                        'tanggal_persetujuan' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    Notification::make()
                        ->title('Catatan Revisi Terkirim ke Humas')
                        ->warning()
                        ->send();
                }),

            // Aksi Kepala Sekolah: Tolak
            Actions\Action::make('tolak')
                ->label('Tolak')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $this->record->status_persetujuan !== 'Ditolak')
                ->form([
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->placeholder('Tuliskan alasan kegiatan ini ditolak...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_persetujuan' => 'Ditolak',
                        'catatan_kepsek' => $data['catatan_kepsek'],
                        'tanggal_persetujuan' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    Notification::make()
                        ->title('Kegiatan Wali Murid Ditolak')
                        ->danger()
                        ->send();
                }),

            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
        ];
    }
}
