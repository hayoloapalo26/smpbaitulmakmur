<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtracurricularGrade extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'nilai_angka' => 'float',
    ];

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class, 'extracurricular_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function pembina()
    {
        return $this->belongsTo(User::class, 'pembina_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
