<?php

namespace App\Filament\Staff\Resources\ExtracurricularJournalResource\Pages;

use App\Filament\Staff\Resources\ExtracurricularJournalResource;
use App\Models\ExtracurricularJournal;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewExtracurricularJournal extends ViewRecord
{
    protected static string $resource = ExtracurricularJournalResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isKesiswaan = $user && $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']);

        return [
            // Tombol Terima Laporan khusus Kesiswaan
            Actions\Action::make('terima_laporan')
                ->label('✅ Terima Laporan')
                ->color('success')
                ->visible(fn () => $isKesiswaan && $this->record->status_verifikasi !== 'diterima')
                ->modalHeading("Verifikasi & Terima Laporan: {$this->record->extracurricular?->name}")
                ->modalDescription('Verifikasi kegiatan ekstrakurikuler ini dan berikan catatan evaluasi untuk Guru Pembina.')
                ->form([
                    Forms\Components\Select::make('status_verifikasi')
                        ->label('Keputusan Verifikasi')
                        ->options([
                            'diterima'     => '✅ Diterima (Disetujui Sesuai Pelaksanaan)',
                            'perlu_revisi' => '⚠️ Perlu Revisi / Perbaikan',
                        ])
                        ->default('diterima')
                        ->required(),

                    Forms\Components\Textarea::make('catatan_kesiswaan')
                        ->label('Catatan & Feedback Kesiswaan')
                        ->placeholder('Uraikan evaluasi, masukan, atau apresiasi...')
                        ->default('Laporan kegiatan ekstrakurikuler telah diverifikasi dan disetujui oleh Waka Kesiswaan.')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_verifikasi' => $data['status_verifikasi'],
                        'catatan_kesiswaan' => $data['catatan_kesiswaan'],
                        'verified_at'       => now(),
                        'verified_by'       => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Laporan Berhasil Diverifikasi')
                        ->body("Status jurnal telah diubah menjadi " . ucfirst($data['status_verifikasi']))
                        ->success()
                        ->send();

                    $this->fillForm();
                }),

            // Tombol Beri Catatan
            Actions\Action::make('beri_catatan')
                ->label('💬 Beri Catatan Kesiswaan')
                ->color('warning')
                ->visible(fn () => $isKesiswaan)
                ->modalHeading("Beri Catatan untuk Pembina: {$this->record->extracurricular?->name}")
                ->fillForm(fn () => [
                    'catatan_kesiswaan' => $this->record->catatan_kesiswaan,
                ])
                ->form([
                    Forms\Components\Textarea::make('catatan_kesiswaan')
                        ->label('Catatan Kesiswaan')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'catatan_kesiswaan' => $data['catatan_kesiswaan'],
                    ]);

                    Notification::make()
                        ->title('Catatan Berhasil Disimpan')
                        ->success()
                        ->send();

                    $this->fillForm();
                }),

            // Edit hanya untuk guru pembina (jika belum diterima) atau superadmin
            Actions\EditAction::make()
                ->visible(fn () => $user && ($user->hasRole('superadmin') || ($user->hasRole('guru_ekstrakurikuler') && $this->record->status_verifikasi !== 'diterima'))),
        ];
    }
}
