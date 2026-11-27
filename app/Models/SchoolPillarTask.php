<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;

class SchoolPillarTask extends Model
{
    use BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'is_sop_merah' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function pillar()
    {
        return $this->belongsTo(SchoolPillar::class, 'school_pillar_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
