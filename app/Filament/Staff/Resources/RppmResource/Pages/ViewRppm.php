<?php

namespace App\Filament\Staff\Resources\RppmResource\Pages;

use App\Filament\Staff\Resources\RppmResource;
use App\Models\Rppm;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRppm extends ViewRecord
{
    protected static string $resource = RppmResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cetak_pdf')
                ->label('🖨️ Cetak PDF')
                ->color('danger')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('academic.rppm.pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\EditAction::make()
                ->label('✏️ Edit / Perbaiki')
                ->color('warning')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (): bool => !auth()->user()?->hasRole('kepala_sekolah') && (auth()->id() === $this->record->guru_id || auth()->user()?->hasAnyRole(['superadmin', 'waka_kurikulum']))),

            Actions\DeleteAction::make()
                ->label('🗑️ Hapus RPPM')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->modalHeading('Hapus Dokumen RPPM & Perangkat Ajar')
                ->modalDescription('Apakah Anda yakin ingin menghapus dokumen RPPM ini? Data yang telah dihapus tidak dapat dikembalikan.')
                ->modalSubmitActionLabel('Ya, Hapus')
                ->successNotificationTitle('Dokumen RPPM Berhasil Dihapus')
                ->visible(fn (): bool => !auth()->user()?->hasRole('kepala_sekolah') && (auth()->id() === $this->record->guru_id || auth()->user()?->hasAnyRole(['superadmin', 'waka_kurikulum'])))
                ->successRedirectUrl(RppmResource::getUrl('index')),

            // Tombol ACC / Setujui untuk Kepala Sekolah & Superadmin
            Actions\Action::make('approve_rppm')
                ->label('✅ ACC / Setujui RPPM')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->visible(fn (): bool => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                ->modalHeading('ACC / Setujui RPPM')
                ->modalDescription('Apakah Anda yakin ingin menyetujui RPPM ini? Anda dapat menambahkan catatan supervisi atau apresiasi.')
                ->modalSubmitActionLabel('Setujui (ACC)')
                ->form([
                    Forms\Components\Textarea::make('catatan_kepala_sekolah')
                        ->label('Catatan Supervisi / Persetujuan (Opsional)')
                        ->placeholder('Contoh: RPPM telah sesuai dengan capaian pembelajaran dan alur tujuan kurikulum. Disetujui.')
                        ->default(fn () => $this->record->catatan_kepala_sekolah)
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'approved',
                        'catatan_kepala_sekolah' => $data['catatan_kepala_sekolah'] ?? null,
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ]);

                    Notification::make()
                        ->title('RPPM Berhasil Disetujui (ACC)')
                        ->body("RPPM {$this->record->mata_pelajaran} kelas {$this->record->kelas_fase} telah disetujui.")
                        ->success()
                        ->send();
                }),

            // Tombol Perbaikan untuk Kepala Sekolah & Superadmin
            Actions\Action::make('revise_rppm')
                ->label('⚠️ Minta Perbaikan')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                ->modalHeading('Minta Perbaikan / Revisi RPPM')
                ->modalDescription('Kembalikan RPPM kepada guru untuk diperbaiki. Harap sertakan catatan perbaikan yang jelas agar guru dapat menindaklanjutinya.')
                ->modalSubmitActionLabel('Kirim Catatan Perbaikan')
                ->form([
                    Forms\Components\Textarea::make('catatan_kepala_sekolah')
                        ->label('Catatan Perbaikan dari Kepala Sekolah')
                        ->placeholder('Contoh: Mohon perbaiki bagian kegiatan inti pembelajaran serta lengkapi rubrik asesmen formatif.')
                        ->default(fn () => $this->record->catatan_kepala_sekolah)
                        ->required()
                        ->rows(4)
                        ->helperText('Catatan ini akan langsung tampil di akun guru penyusun RPPM.'),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'need_revision',
                        'catatan_kepala_sekolah' => $data['catatan_kepala_sekolah'],
                        'reviewed_by' => auth()->id(),
                        'reviewed_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Status RPPM Diubah Menjadi Perbaikan')
                        ->body("Catatan perbaikan telah berhasil dikirim ke guru penyusun.")
                        ->warning()
                        ->send();
                }),
        ];
    }
}
