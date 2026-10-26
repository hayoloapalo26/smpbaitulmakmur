<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectAttendance extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    //

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teachingJournal()
    {
        return $this->belongsTo(TeachingJournal::class, 'teaching_journal_id');
    }
}
