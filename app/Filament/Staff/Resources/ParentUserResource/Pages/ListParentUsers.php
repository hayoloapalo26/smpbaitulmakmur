<?php

namespace App\Filament\Staff\Resources\ParentUserResource\Pages;

use App\Filament\Staff\Resources\ParentUserResource;
use App\Services\GuardianService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListParentUsers extends ListRecords
{
    protected static string $resource = ParentUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Akun Orang Tua'),

            Actions\Action::make('bersihkan_duplikat')
                ->label('Bersihkan & Gabungkan Duplikat')
                ->icon('heroicon-m-sparkles')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Bersihkan & Gabungkan Akun Duplikat')
                ->modalDescription('Secara otomatis memeriksa dan merapikan akun-akun orang tua kembar/duplikat menjadi 1 akun tunggal aktif.')
                ->action(function () {
                    GuardianService::deduplicateAll();
                    Notification::make()
                        ->title('Deduplikasi Berhasil')
                        ->body('Data dan akun orang tua berhasil dirapikan menjadi 1 akun.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
