<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OsisProgram extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    //
}
