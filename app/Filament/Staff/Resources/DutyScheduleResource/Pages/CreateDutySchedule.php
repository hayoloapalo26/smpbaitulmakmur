<?php

namespace App\Filament\Staff\Resources\DutyScheduleResource\Pages;

use App\Filament\Staff\Resources\DutyScheduleResource;
use App\Models\AcademicYear;
use App\Models\DutySchedule;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateDutySchedule extends CreateRecord
{
    protected static string $resource = DutyScheduleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $teacherIds = (array) ($data['teacher_ids'] ?? []);
        if (empty($teacherIds) && !empty($data['teacher_id'])) {
            $teacherIds = [$data['teacher_id']];
        }

        $hari = $data['hari'];
        $semester = $data['semester'] ?? ((Carbon::now()->month >= 7 && Carbon::now()->month <= 12) ? 'Ganjil' : 'Genap');
        $academicYearId = $data['academic_year_id'] ?? AcademicYear::where('is_active', true)->first()?->id;
        $instId = Auth::user()?->institution_id;
        $notes = $data['notes'] ?? null;
        $tanggal = $data['tanggal'] ?? null;

        $firstRecord = null;
        foreach ($teacherIds as $teacherId) {
            $record = DutySchedule::updateOrCreate(
                [
                    'hari' => $hari,
                    'teacher_id' => $teacherId,
                    'semester' => $semester,
                    'academic_year_id' => $academicYearId,
                ],
                [
                    'institution_id' => $instId,
                    'tanggal' => $tanggal,
                    'notes' => $notes,
                ]
            );

            if (!$firstRecord) {
                $firstRecord = $record;
            }
        }

        return $firstRecord ?? new DutySchedule();
    }
}
