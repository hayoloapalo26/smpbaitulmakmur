<?php

namespace App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource\Pages;

use App\Filament\Staff\Resources\PerbaikanPenggantianSarprasResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPerbaikanPenggantianSarpras extends ViewRecord
{
    protected static string $resource = PerbaikanPenggantianSarprasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Aksi Kepala Sekolah: ACC / Setujui Tindakan & Anggaran
            Actions\Action::make('setujui')
                ->label('ACC Tindakan & Anggaran')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && $this->record->status_persetujuan !== 'Disetujui')
                ->requiresConfirmation()
                ->modalHeading('ACC Penanganan Sarpras (Perbaikan / Penggantian)')
                ->modalDescription('Apakah Anda menyetujui rencana tindakan dan alokasi anggaran penanganan untuk barang inventaris ini?')
                ->modalSubmitActionLabel('Ya, Setujui (ACC)')
                ->form([
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Catatan Disposisi / Instruksi Tindak Lanjut (Opsional)')
                        ->placeholder('Tuliskan arahan disposisi atau petunjuk penganggaran jika ada...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_persetujuan' => 'Disetujui',
                        'catatan_kepsek' => !empty($data['catatan_kepsek']) ? $data['catatan_kepsek'] : $this->record->catatan_kepsek,
                        'tanggal_persetujuan' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    Notification::make()
                        ->title('Penanganan Sarpras Berhasil Disetujui (ACC)')
                        ->success()
                        ->send();
                }),

            // Aksi Kepala Sekolah: Minta Evaluasi Ulang
            Actions\Action::make('evaluasi')
                ->label('Minta Evaluasi Ulang')
                ->icon('heroicon-m-pencil-square')
                ->color('warning')
                ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']) && in_array($this->record->status_persetujuan, ['Menunggu Persetujuan', 'Disetujui']))
                ->form([
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Catatan Evaluasi / Arahan')
                        ->required()
                        ->placeholder('Tuliskan pertimbangan biaya, opsi perbaikan alternatif, atau investigasi penyebab kerusakan/kehilangan...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_persetujuan' => 'Perlu Evaluasi Ulang',
                        'catatan_kepsek' => $data['catatan_kepsek'],
                        'tanggal_persetujuan' => now(),
                        'approved_by' => auth()->id(),
                    ]);
                    Notification::make()
                        ->title('Catatan Evaluasi Terkirim ke Bagian Sarpras')
                        ->warning()
                        ->send();
                }),

            // Aksi Tandai Selesai (untuk Staf Sarpras setelah pengerjaan beres)
            Actions\Action::make('tandai_selesai')
                ->label('Tandai Selesai Penanganan')
                ->icon('heroicon-m-check-badge')
                ->color('primary')
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin']) && !in_array($this->record->status_proses, ['Selesai Diperbaiki', 'Selesai Diganti Unit Baru', 'Dihapuskan / Afkir']))
                ->form([
                    Forms\Components\Select::make('status_proses')
                        ->label('Hasil Akhir Penanganan')
                        ->options([
                            'Selesai Diperbaiki' => '🟢 Selesai Diperbaiki (Aset Kembali Berfungsi Normal)',
                            'Selesai Diganti Unit Baru' => '🟢 Selesai Diganti Unit Baru (Aset Diperbarui)',
                            'Dihapuskan / Afkir' => '⚫ Aset Resmi Dihapuskan / Afkir',
                        ])
                        ->default('Selesai Diperbaiki')
                        ->required(),
                    Forms\Components\TextInput::make('realisasi_biaya')
                        ->label('Realisasi Biaya Akhir (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(fn () => $this->record->realisasi_biaya ?: $this->record->estimasi_biaya)
                        ->required(),
                    Forms\Components\DatePicker::make('tanggal_selesai')
                        ->label('Tanggal Selesai Penanganan')
                        ->default(now())
                        ->required(),
                    Forms\Components\Textarea::make('keterangan_penyelesaian')
                        ->label('Catatan Penyelesaian Akhir / Serial Baru / Garansi')
                        ->placeholder('Contoh: Unit telah berfungsi normal setelah diganti power supply, garansi 3 bulan toko...'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status_proses' => $data['status_proses'],
                        'realisasi_biaya' => $data['realisasi_biaya'],
                        'tanggal_selesai' => $data['tanggal_selesai'],
                        'keterangan_penyelesaian' => !empty($data['keterangan_penyelesaian']) ? $data['keterangan_penyelesaian'] : $this->record->keterangan_penyelesaian,
                    ]);

                    Notification::make()
                        ->title('Penanganan Sarpras Ditandai Selesai & Master Inventaris Diperbarui')
                        ->success()
                        ->send();
                }),

            // Aksi Buka Tautan Google Drive Bukti
            Actions\Action::make('buka_drive')
                ->label('Buka Folder Drive')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('info')
                ->url(fn () => $this->record->link_drive_bukti, shouldOpenInNewTab: true)
                ->visible(fn () => !empty($this->record->link_drive_bukti)),

            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['sarpras', 'superadmin'])),
        ];
    }
}
