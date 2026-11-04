<?php

namespace App\Filament\Staff\Resources\ExtracurricularJournalResource\Pages;

use App\Filament\Staff\Resources\ExtracurricularJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExtracurricularJournal extends EditRecord
{
    protected static string $resource = ExtracurricularJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasRole('superadmin')),
        ];
    }

    protected function afterSave(): void
    {
        // Sinkronisasi data extracurricular_id, tanggal, pembina_id, dan institution_id ke setiap presensi siswa
        $this->record->attendances()->update([
            'extracurricular_id' => $this->record->extracurricular_id,
            'tanggal'            => $this->record->tanggal,
            'pembina_id'         => $this->record->pembina_id ?? auth()->id(),
            'institution_id'     => $this->record->institution_id ?? auth()->user()?->institution_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
