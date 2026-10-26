<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YayasanProgram extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    use HasFactory;
    protected $fillable = ['name', 'description'];

    public function institutions()
    {
        return $this->belongsToMany(Institution::class, 'institution_yayasan_program')
            ->withPivot('is_completed', 'notes')
            ->withTimestamps();
    }
}
