<?php

namespace App\Filament\Staff\Resources\TeacherLeaveRequestResource\Pages;

use App\Filament\Staff\Resources\TeacherLeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTeacherLeaveRequests extends ManageRecords
{
    protected static string $resource = TeacherLeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => !auth()->user()?->hasRole('kepala_sekolah'))
                ->mutateFormDataUsing(function (array $data): array {
                    $data['teacher_id'] = $data['teacher_id'] ?? auth()->id();
                    $data['status'] = $data['status'] ?? 'diajukan';
                    if (empty($data['tanggal_selesai']) || ($data['tipe_izin'] ?? 'harian') === 'jam_tertentu') {
                        $data['tanggal_selesai'] = $data['tanggal_mulai'] ?? now()->toDateString();
                    }
                    return $data;
                }),
        ];
    }
}
