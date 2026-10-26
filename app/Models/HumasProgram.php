<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HumasProgram extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    //
}
