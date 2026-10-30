<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    protected $guarded = ['id'];
    public function yayasanPrograms()
    {
        return $this->belongsToMany(YayasanProgram::class, 'institution_yayasan_program')
            ->withPivot('is_completed', 'notes')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'institution_user')->withTimestamps();
    }
}

