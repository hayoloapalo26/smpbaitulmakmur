<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherTodoList extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];
    
    protected $casts = [
        'is_done' => 'boolean',
        'tanggal' => 'date',
        'completed_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
