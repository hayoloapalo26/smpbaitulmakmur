<?php

namespace App\Filament\Staff\Resources\StudentCaseResource\Pages;

use App\Filament\Staff\Resources\StudentCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStudentCases extends ManageRecords
{
    protected static string $resource = StudentCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('+ Laporkan Kasus Baru')
                ->visible(fn () => auth()->user()->hasRole('wali_kelas')),
        ];
    }
}
