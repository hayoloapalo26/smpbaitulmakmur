<?php

namespace App\Filament\Staff\Resources\SchoolWorkPlanResource\Pages;

use App\Filament\Staff\Resources\SchoolWorkPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchoolWorkPlan extends EditRecord
{
    protected static string $resource = SchoolWorkPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($record) => auth()->user()->hasRole('kepala_sekolah') && in_array($record->status, ['draft', 'revision_ketua', 'revision_pembina'])),
        ];
    }
}
