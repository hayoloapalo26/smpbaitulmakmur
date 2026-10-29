<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tp_ids'       => 'array',
        'is_remedial'  => 'boolean',
        'is_pengayaan' => 'boolean',
        'tanggal'      => 'date',
        'tanggal_remedial'  => 'date',
        'tanggal_pengayaan' => 'date',
        'score'            => 'float',
        'nilai_remedial'   => 'float',
        'nilai_pengayaan'  => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function tpPengayaan()
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tp_pengayaan_id');
    }

    public function getSelectedTpsAttribute()
    {
        if (empty($this->tp_ids)) {
            return collect();
        }

        return TujuanPembelajaran::whereIn('id', $this->tp_ids)->get();
    }
}
