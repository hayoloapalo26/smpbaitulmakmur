<?php

namespace App\Filament\Staff\Resources\ExtracurricularJournalResource\Pages;

use App\Filament\Staff\Resources\ExtracurricularJournalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExtracurricularJournal extends CreateRecord
{
    protected static string $resource = ExtracurricularJournalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if (empty($data['pembina_id'])) {
            $data['pembina_id'] = $user?->id;
        }
        if (empty($data['institution_id'])) {
            $data['institution_id'] = $user?->institution_id;
        }
        $data['status_verifikasi'] = 'menunggu_verifikasi';

        return $data;
    }

    protected function afterCreate(): void
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
