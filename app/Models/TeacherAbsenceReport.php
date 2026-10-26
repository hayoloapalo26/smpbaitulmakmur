<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAbsenceReport extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    //

    public function absentTeacher()
    {
        return $this->belongsTo(User::class, 'absent_teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }
}
