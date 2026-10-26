<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterScoreLog extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    //

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
