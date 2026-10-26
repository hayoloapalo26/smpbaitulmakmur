<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YayasanEmployee extends Model
{
    protected $casts = [
        'join_date' => 'date',
        'resign_date' => 'date',
        'is_active' => 'boolean',
    ];
    use HasFactory;
    protected $fillable = ['name', 'position', 'education', 'address', 'phone', 'email', 'join_date', 'is_active', 'resign_date'];

    public function tasks()
    {
        return $this->hasMany(YayasanEmployeeTask::class);
    }
}


