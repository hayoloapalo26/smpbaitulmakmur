<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    public function teacherSubjectClasses()
    {
        return $this->hasMany(TeacherSubjectClass::class, 'subject_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'teacher_subject_classes', 'subject_id', 'teacher_id')->distinct();
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'subject_id');
    }
}
