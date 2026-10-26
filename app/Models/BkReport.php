<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BkReport extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    //

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function studentCase()
    {
        return $this->belongsTo(StudentCase::class, 'student_case_id');
    }

    public function bkTeacher()
    {
        return $this->belongsTo(User::class, 'bk_teacher_id');
    }
}
