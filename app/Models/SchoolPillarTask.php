<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolPillarTask extends Model
{
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
