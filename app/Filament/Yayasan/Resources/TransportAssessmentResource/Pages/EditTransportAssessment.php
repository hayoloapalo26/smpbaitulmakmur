<?php

namespace App\Filament\Yayasan\Resources\TransportAssessmentResource\Pages;

use App\Filament\Yayasan\Resources\TransportAssessmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransportAssessment extends EditRecord
{
    protected static string $resource = TransportAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
