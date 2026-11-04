<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaporGrade extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'is_custom'                 => 'boolean',
        'nilai_pengetahuan_asli'    => 'float',
        'nilai_keterampilan_asli'   => 'float',
        'nilai_akhir_asli'          => 'float',
        'nilai_pengetahuan_olah'    => 'float',
        'nilai_keterampilan_olah'   => 'float',
        'nilai_akhir_olah'          => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
