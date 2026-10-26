<?php

namespace App\Filament\Staff\Resources\SchoolWorkPlanResource\Pages;

use App\Filament\Staff\Resources\SchoolWorkPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchoolWorkPlans extends ListRecords
{
    protected static string $resource = SchoolWorkPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->hasRole('kepala_sekolah')),
        ];
    }
}
