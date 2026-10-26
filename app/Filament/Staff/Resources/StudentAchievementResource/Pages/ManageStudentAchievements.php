<?php

namespace App\Filament\Staff\Resources\StudentAchievementResource\Pages;

use App\Filament\Staff\Resources\StudentAchievementResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentAchievements extends ManageRecords
{
    protected static string $resource = StudentAchievementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Prestasi Siswa')
                ->icon('heroicon-o-trophy')
                ->color('warning')
                ->modalHeading('Input Prestasi Siswa Baru')
                ->mutateFormDataUsing(function (array $data): array {
                    $student = \App\Models\Student::withoutGlobalScopes()->find($data['student_id'] ?? null);
                    $data['institution_id'] = $student?->institution_id ?? auth()->user()?->institution_id;
                    $data['input_by'] = auth()->id();
                    return $data;
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Staff\Widgets\StudentAchievementStatsOverviewWidget::class,
        ];
    }
}

