<?php

namespace App\Filament\Staff\Resources\ClassProgramResource\Pages;

use App\Filament\Staff\Resources\ClassProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageClassPrograms extends ManageRecords
{
    protected static string $resource = ClassProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Program Kelas')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
