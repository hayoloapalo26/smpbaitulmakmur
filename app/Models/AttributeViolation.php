<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeViolation extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'attribute_type' => 'array',
    ];

    //

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
