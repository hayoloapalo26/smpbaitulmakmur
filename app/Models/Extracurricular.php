<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Extracurricular extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    //

    public function pembina()
    {
        return $this->belongsTo(User::class, 'pembina_id');
    }
}
