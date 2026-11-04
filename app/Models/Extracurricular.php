<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Extracurricular extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pembina()
    {
        return $this->belongsTo(User::class, 'pembina_id');
    }

    public function attendances()
    {
        return $this->hasMany(ExtracurricularAttendance::class, 'extracurricular_id');
    }

    public function members()
    {
        return $this->hasMany(ExtracurricularMember::class, 'extracurricular_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'extracurricular_members', 'extracurricular_id', 'student_id')
            ->withPivot(['is_active', 'catatan'])
            ->withTimestamps();
    }

    public function journals()
    {
        return $this->hasMany(ExtracurricularJournal::class, 'extracurricular_id');
    }

    public function grades()
    {
        return $this->hasMany(ExtracurricularGrade::class, 'extracurricular_id');
    }
}
