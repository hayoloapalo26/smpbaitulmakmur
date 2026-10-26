<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToInstitution;

class SchoolPillar extends Model
{
    use BelongsToInstitution;

    protected $guarded = ['id'];

    public function tasks()
    {
        return $this->hasMany(SchoolPillarTask::class, 'school_pillar_id');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}
