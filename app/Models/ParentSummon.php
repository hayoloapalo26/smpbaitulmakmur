<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentSummon extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function studentCase()
    {
        return $this->belongsTo(StudentCase::class, 'student_case_id');
    }
}
