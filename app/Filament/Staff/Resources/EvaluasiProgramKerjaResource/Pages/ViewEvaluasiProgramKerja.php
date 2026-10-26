<?php

namespace App\Filament\Staff\Resources\EvaluasiProgramKerjaResource\Pages;

use App\Filament\Staff\Resources\EvaluasiProgramKerjaResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewEvaluasiProgramKerja extends ViewRecord
{
    protected static string $resource = EvaluasiProgramKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Aksi Kepala Sekolah: Beri Arahan / Catatan Review
            Actions\Action::make('tinjauKepsek')
                ->label('Beri Arahan / Catatan KS')
                ->icon('heroicon-m-pencil-square')
                ->color('warning')
                ->visible(fn () => auth()->user()?->hasAnyRole(['kepala_sekolah', 'superadmin']))
                ->form([
                    Forms\Components\Textarea::make('catatan_kepsek')
                        ->label('Tanggapan & Arahan Kepala Sekolah')
                        ->required()
                        ->placeholder('Tuliskan masukan, apresiasi, atau arahan perbaikan terhadap hasil evaluasi kegiatan ini...')
                        ->default(fn () => $this->record->catatan_kepsek),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'catatan_kepsek' => $data['catatan_kepsek'],
                        'reviewed_by' => auth()->id(),
                        'tanggal_review' => now(),
                    ]);
                    Notification::make()
                        ->title('Catatan Kepala Sekolah Berhasil Disimpan')
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['humas', 'superadmin'])),
        ];
    }
}
