<?php

namespace App\Filament\Staff\Resources\AlumniTracerResource\Pages;

use App\Filament\Staff\Resources\AlumniTracerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

use App\Filament\Staff\Widgets\AlumniTracerStatsOverviewWidget;

class ManageAlumniTracers extends ManageRecords
{
    protected static string $resource = AlumniTracerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Catat Data Alumni')
                ->icon('heroicon-o-academic-cap')
                ->color('warning')
                ->modalHeading('Catat Penelusuran Alumni (Tracer Study)')
                ->mutateFormDataUsing(function (array $data): array {
                    if (!empty($data['student_id'])) {
                        $student = \App\Models\Student::withoutGlobalScopes()->find($data['student_id']);
                        $data['institution_id'] = $student?->institution_id ?? auth()->user()?->institution_id;
                    } else {
                        $data['institution_id'] = auth()->user()?->institution_id;
                    }
                    $data['input_by'] = auth()->id();
                    return $data;
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AlumniTracerStatsOverviewWidget::class,
        ];
    }
}
