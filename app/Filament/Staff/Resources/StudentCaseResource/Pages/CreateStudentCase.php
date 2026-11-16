<?php

namespace App\Filament\Staff\Resources\StudentCaseResource\Pages;

use App\Filament\Staff\Resources\StudentCaseResource;
use App\Models\Student;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentCase extends CreateRecord
{
    protected static string $resource = StudentCaseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if (!empty($data['student_id'])) {
            $student = Student::withoutGlobalScopes()->with(['classroom' => fn ($q) => $q->withoutGlobalScopes()])->find($data['student_id']);
            if ($student?->classroom?->wali_kelas_id) {
                $data['wali_kelas_id'] = $student->classroom->wali_kelas_id;
            }
            if (empty($data['institution_id'])) {
                $data['institution_id'] = $student?->institution_id ?? $user?->institution_id;
            }
        }

        if (empty($data['wali_kelas_id'])) {
            $data['wali_kelas_id'] = $user?->id ?? 1;
        }

        if ($user && $user->hasRole('waka_kesiswaan') && !$user->hasRole('superadmin')) {
            $data['recommended_to_kesiswaan'] = true;
            $data['ditangani_oleh'] = 'kesiswaan';
        } else {
            $data['ditangani_oleh'] = $data['ditangani_oleh'] ?? 'wali_kelas';
        }

        $data['status'] = 'proses';
        if (empty($data['poin_pelanggaran'])) {
            $data['poin_pelanggaran'] = 5;
        }

        return $data;
    }
}
