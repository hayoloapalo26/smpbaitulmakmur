<?php

namespace App\Filament\Yayasan\Resources\StudentSppResource\Pages;

use App\Filament\Yayasan\Resources\StudentSppResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentSpps extends ManageRecords
{
    protected static string $resource = StudentSppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pembayaran SPP')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['petugas_id'] = auth()->id();
                    return $data;
                }),
        ];
    }
}
