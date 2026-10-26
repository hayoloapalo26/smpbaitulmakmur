<?php

namespace App\Filament\Staff\Resources\CharacterScoreLogResource\Pages;

use App\Filament\Staff\Resources\CharacterScoreLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCharacterScoreLogs extends ManageRecords
{
    protected static string $resource = CharacterScoreLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $negativeTraits = ['Ramai', 'Ganggu Teman', 'Alpa', 'Terlambat', 'Tidak Berani Maju', 'Tidak Mengerjakan PR/Tugas'];
                    if (in_array($data['description'], $negativeTraits)) {
                        $data['point_change'] = -1;
                    } else {
                        $data['point_change'] = 0;
                    }
                    return $data;
                }),
        ];
    }
}
