<?php

namespace App\Filament\Staff\Resources\RencanaKebutuhanSarprasResource\Pages;

use App\Filament\Staff\Resources\RencanaKebutuhanSarprasResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRencanaKebutuhanSarpras extends ViewRecord
{
    protected static string $resource = RencanaKebutuhanSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Aksi Ajukan ke Kepala Sekolah (untuk Staf Sarpras)
            Actions\Action::make('ajukan')
                ->label('Ajukan ke Kepala Sekolah')
                ->icon('heroicon-m-paper-airplane')
                ->color('primary')
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin']) && $this->record->status_pelaksanaan === 'Draft')
                ->requiresConfirmation()
                ->modalHeading('Ajukan Rencana Kebutuhan Sarpras')
                ->modalDescription('Apakah dokumen rencana kebutuhan sarpras ini sudah lengkap dan siap diajukan untuk ditinjau oleh Kepala Sekolah?')
                ->action(function () {
                    $this->record->update([
                        'status_pelaksanaan' => 'Diajukan',
                        'status_persetujuan' => 'Menunggu Persetujuan',
                    ]);
                    Notification::make()
                        ->title('Rencana Kebutuhan Berhasil Diajukan ke Kepala Sekolah')
                        ->success()
                        ->send();
                }),

            // Aksi Kepala Sekolah: ACC / Setujui
            Actions\Action::make('setujui')
                ->label('ACC Rencana Kebutuhan')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $this->record->status_persetujuan !== 'Disetujui')
                ->requiresConfirmation()
                ->modalHeading('ACC Rencana Kebutuhan Sarpras')
                ->modalDescription('Apakah Anda yakin menyetujui rencana kebutuhan sarana dan prasarana ini untuk diteruskan ke tahap pengadaan?')
                ->modalSubmitActionLabel('Ya, Setujui (ACC)')
                ->form([
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Catatan / Arahan Tambahan (Opsional)')
                        ->placeholder('Tuliskan catatan arahan terkait pengadaan, vendor, atau prioritas penganggaran jika ada...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_persetujuan' => 'Disetujui',
                        'status_pelaksanaan' => 'Disetujui untuk Pengadaan',
                        'catatan_kepsek' => !empty($data['catatan_kepsek']) ? $data['catatan_kepsek'] : $this->record->catatan_kepsek,
                        'tanggal_persetujuan' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    Notification::make()
                        ->title('Rencana Kebutuhan Sarpras Berhasil Disetujui (ACC)')
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
                        ->label('Catatan Perbaikan / Revisi')
                        ->required()
                        ->placeholder('Tuliskan poin spesifikasi, volume, atau estimasi anggaran yang perlu disesuaikan oleh staf Sarpras...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_persetujuan' => 'Perlu Perbaikan',
                        'catatan_kepsek' => $data['catatan_kepsek'],
                        'tanggal_persetujuan' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    Notification::make()
                        ->title('Catatan Revisi Terkirim ke Bagian Sarpras')
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
                        ->placeholder('Tuliskan alasan penolakan rencana kebutuhan sarpras ini...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_persetujuan' => 'Ditolak',
                        'status_pelaksanaan' => 'Dibatalkan',
                        'catatan_kepsek' => $data['catatan_kepsek'],
                        'tanggal_persetujuan' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    Notification::make()
                        ->title('Rencana Kebutuhan Sarpras Ditolak')
                        ->danger()
                        ->send();
                }),

            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
        ];
    }
}
