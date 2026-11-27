<?php

namespace App\Filament\Staff\Resources\GuardianResource\Pages;

use App\Filament\Staff\Resources\GuardianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuardians extends ListRecords
{
    protected static string $resource = GuardianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Data Orang Tua'),

            Actions\Action::make('gabungkan_duplikat')
                ->label('Bersihkan & Gabungkan Duplikat')
                ->icon('heroicon-m-sparkles')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Bersihkan & Gabungkan Data Duplikat Orang Tua')
                ->modalDescription('Fitur ini akan secara otomatis mendeteksi nama orang tua yang ganda (duplikat), menggabungkan seluruh anak ke 1 data orang tua tunggal, dan menautkannya ke 1 akun login resmi.')
                ->action(function () {
                    $count = \App\Services\GuardianService::deduplicateAll();
                    \Filament\Notifications\Notification::make()
                        ->title('Pembersihan Duplikat Selesai')
                        ->body("Data orang tua duplikat berhasil dirapikan dan disatukan menjadi 1 akun resmi.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
