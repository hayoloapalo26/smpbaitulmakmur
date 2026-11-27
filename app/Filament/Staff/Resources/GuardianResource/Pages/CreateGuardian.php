<?php

namespace App\Filament\Staff\Resources\GuardianResource\Pages;

use App\Filament\Staff\Resources\GuardianResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuardian extends CreateRecord
{
    protected static string $resource = GuardianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $name = trim($data['name']);
        $instId = !empty($data['institution_id']) ? $data['institution_id'] : (auth()->user()?->institution_id ?: 2);

        // Cek apakah data orang tua dengan nama ini sudah ada di sistem
        $existing = \App\Models\Guardian::where('institution_id', $instId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->first();

        if ($existing) {
            \Filament\Notifications\Notification::make()
                ->title('Data Orang Tua Sudah Ada')
                ->body("Data orang tua atas nama '{$name}' sudah terdaftar. 1 Orang tua cukup memiliki 1 data untuk seluruh anak-anaknya. Silakan gunakan tombol 'Routing Siswa' untuk menghubungkan anak.")
                ->warning()
                ->send();
            $this->halt();
        }

        $data['name'] = $name;
        $data['institution_id'] = $instId;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
