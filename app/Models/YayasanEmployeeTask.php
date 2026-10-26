<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YayasanEmployeeTask extends Model
{
    use HasFactory;
    protected $fillable = ['yayasan_employee_id', 'task_name', 'is_completed'];

    public function employee()
    {
        return $this->belongsTo(YayasanEmployee::class, 'yayasan_employee_id');
    }
}

