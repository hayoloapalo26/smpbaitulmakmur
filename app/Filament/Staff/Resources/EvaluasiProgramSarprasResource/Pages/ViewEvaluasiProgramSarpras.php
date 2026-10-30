<?php

namespace App\Filament\Staff\Resources\EvaluasiProgramSarprasResource\Pages;

use App\Filament\Staff\Resources\EvaluasiProgramSarprasResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewEvaluasiProgramSarpras extends ViewRecord
{
    protected static string $resource = EvaluasiProgramSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Aksi Kepala Sekolah: ACC / Setujui Hasil Evaluasi
            Actions\Action::make('setujui_evaluasi')
                ->label('ACC Hasil Evaluasi')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $this->record->status_review !== 'Disetujui (Selesai Dievaluasi)')
                ->requiresConfirmation()
                ->modalHeading('ACC Laporan Evaluasi Program Kerja Sarpras')
                ->modalDescription('Apakah Anda menyetujui hasil evaluasi keterlaksanaan program kerja sarpras ini?')
                ->modalSubmitActionLabel('Ya, Setujui (ACC)')
                ->form([
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Catatan Review / Apresiasi / Arahan Kepala Sekolah (Opsional)')
                        ->placeholder('Tuliskan tanggapan, evaluasi capaian kinerja, atau arahan program tindak lanjut...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_review' => 'Disetujui (Selesai Dievaluasi)',
                        'catatan_kepsek' => !empty($data['catatan_kepsek']) ? $data['catatan_kepsek'] : $this->record->catatan_kepsek,
                        'reviewed_by' => auth()->id(),
                        'tanggal_review' => now(),
                    ]);

                    Notification::make()
                        ->title('Laporan Evaluasi Program Sarpras Berhasil Disetujui (ACC)')
                        ->success()
                        ->send();
                }),

            // Aksi Kepala Sekolah: Minta Perbaikan Laporan Evaluasi
            Actions\Action::make('revisi_evaluasi')
                ->label('Minta Revisi Evaluasi')
                ->icon('heroicon-m-pencil-square')
                ->color('warning')
                ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && in_array($this->record->status_review, ['Menunggu Review', 'Disetujui (Selesai Dievaluasi)']))
                ->form([
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Catatan Perbaikan / Arahan')
                        ->required()
                        ->placeholder('Tuliskan poin realisasi atau kelengkapan berkas LPJ yang perlu disempurnakan oleh staf Sarpras...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_review' => 'Perlu Perbaikan Laporan',
                        'catatan_kepsek' => $data['catatan_kepsek'],
                        'reviewed_by' => auth()->id(),
                        'tanggal_review' => now(),
                    ]);

                    Notification::make()
                        ->title('Catatan Revisi Evaluasi Terkirim ke Bagian Sarpras')
                        ->warning()
                        ->send();
                }),

            // Aksi Buka Tautan Google Drive LPJ
            Actions\Action::make('buka_drive')
                ->label('Buka Folder LPJ')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('info')
                ->url(fn () => $this->record->link_drive_lpj, shouldOpenInNewTab: true)
                ->visible(fn () => !empty($this->record->link_drive_lpj)),

            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
        ];
    }
}
