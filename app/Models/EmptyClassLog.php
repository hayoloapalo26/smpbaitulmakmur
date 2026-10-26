<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmptyClassLog extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    //

    public function dutyTeacher()
    {
        return $this->belongsTo(User::class, 'duty_teacher_id');
    }

    public function teacherAbsenceReport()
    {
        return $this->belongsTo(TeacherAbsenceReport::class, 'teacher_absence_report_id');
    }
}
