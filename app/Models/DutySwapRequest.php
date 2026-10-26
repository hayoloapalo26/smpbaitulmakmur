<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutySwapRequest extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    //

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(User::class, 'substitute_teacher_id');
    }
}
