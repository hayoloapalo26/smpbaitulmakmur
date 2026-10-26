<?php

namespace App\Filament\Yayasan\Resources\YayasanProgramResource\Pages;

use App\Filament\Yayasan\Resources\YayasanProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditYayasanProgram extends EditRecord
{
    protected static string $resource = YayasanProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
