<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtracurricularMember extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class, 'extracurricular_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
