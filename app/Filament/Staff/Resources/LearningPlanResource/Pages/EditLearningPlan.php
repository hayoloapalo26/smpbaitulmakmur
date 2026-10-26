<?php

namespace App\Filament\Staff\Resources\LearningPlanResource\Pages;

use App\Filament\Staff\Resources\LearningPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLearningPlan extends EditRecord
{
    protected static string $resource = LearningPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
