<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtracurricularAttendance extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class, 'extracurricular_id');
    }

    public function pembina()
    {
        return $this->belongsTo(User::class, 'pembina_id');
    }

    public function journal()
    {
        return $this->belongsTo(ExtracurricularJournal::class, 'extracurricular_journal_id');
    }
}
