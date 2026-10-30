<?php

namespace App\Filament\Staff\Resources\DutyScheduleResource\Pages;

use App\Filament\Staff\Resources\DutyScheduleResource;
use App\Models\AcademicYear;
use App\Models\DutySchedule;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditDutySchedule extends EditRecord
{
    protected static string $resource = DutyScheduleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['teacher_ids'] = [$this->record->teacher_id];
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $teacherIds = (array) ($data['teacher_ids'] ?? []);
        if (empty($teacherIds) && !empty($data['teacher_id'])) {
            $teacherIds = [$data['teacher_id']];
        }

        $hari = $data['hari'];
        $semester = $data['semester'] ?? $record->semester;
        $academicYearId = $data['academic_year_id'] ?? $record->academic_year_id;
        $instId = Auth::user()?->institution_id ?? $record->institution_id;
        $notes = $data['notes'] ?? null;
        $tanggal = $data['tanggal'] ?? null;

        $firstTeacherId = array_shift($teacherIds);
        if ($firstTeacherId) {
            $record->update([
                'hari' => $hari,
                'teacher_id' => $firstTeacherId,
                'semester' => $semester,
                'academic_year_id' => $academicYearId,
                'institution_id' => $instId,
                'tanggal' => $tanggal,
                'notes' => $notes,
            ]);
        }

        foreach ($teacherIds as $extraTeacherId) {
            DutySchedule::updateOrCreate(
                [
                    'hari' => $hari,
                    'teacher_id' => $extraTeacherId,
                    'semester' => $semester,
                    'academic_year_id' => $academicYearId,
                ],
                [
                    'institution_id' => $instId,
                    'tanggal' => $tanggal,
                    'notes' => $notes,
                ]
            );
        }

        return $record;
    }
}
