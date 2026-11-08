<?php

namespace App\Filament\Staff\Resources\PrincipalTeacherEvaluationResource\Pages;

use App\Filament\Staff\Resources\PrincipalTeacherEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManagePrincipalTeacherEvaluations extends ManageRecords
{
    protected static string $resource = PrincipalTeacherEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Penilaian Guru Baru')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn () => Auth::user()?->hasAnyRole(['superadmin', 'kepala_sekolah']) ?? false),
        ];
    }
}
