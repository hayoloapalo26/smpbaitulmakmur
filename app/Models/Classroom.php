<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\InstitutionScope;

class Classroom extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::addGlobalScope(new InstitutionScope);
    }

    public function waliKelas()
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }
    
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'classroom_id');
    }

    public function classPrograms()
    {
        return $this->hasMany(ClassProgram::class, 'classroom_id');
    }
}