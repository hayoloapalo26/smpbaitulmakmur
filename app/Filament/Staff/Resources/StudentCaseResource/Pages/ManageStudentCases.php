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
                ->visible(fn () => auth()->user()->hasAnyRole(['wali_kelas', 'waka_kesiswaan', 'superadmin']))
                ->mutateFormDataUsing(function (array $data): array {
                    $user = auth()->user();
                    if ($user && $user->hasRole('waka_kesiswaan') && !$user->hasRole('superadmin')) {
                        $data['recommended_to_kesiswaan'] = true;
                        $data['ditangani_oleh'] = 'kesiswaan';
                    }
                    if (empty($data['wali_kelas_id']) && !empty($data['student_id'])) {
                        $st = \App\Models\Student::with('classroom')->find($data['student_id']);
                        if ($st?->classroom?->wali_kelas_id) {
                            $data['wali_kelas_id'] = $st->classroom->wali_kelas_id;
                        }
                    }
                    return $data;
                }),
        ];
    }
}
