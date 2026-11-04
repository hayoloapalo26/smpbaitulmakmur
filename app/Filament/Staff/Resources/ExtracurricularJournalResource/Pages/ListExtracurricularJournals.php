<?php

namespace App\Filament\Staff\Resources\ExtracurricularJournalResource\Pages;

use App\Filament\Staff\Resources\ExtracurricularJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExtracurricularJournals extends ListRecords
{
    protected static string $resource = ExtracurricularJournalResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $canCreate = $user && ($user->hasRole('guru_ekstrakurikuler') || $user->hasRole('superadmin'));

        return [
            Actions\CreateAction::make()
                ->label('➕ Tulis Jurnal Mengajar Eskul')
                ->visible($canCreate),
        ];
    }
}
