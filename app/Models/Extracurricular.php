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
}
