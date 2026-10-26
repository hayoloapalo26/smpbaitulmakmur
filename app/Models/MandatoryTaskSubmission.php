<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MandatoryTaskSubmission extends Model
{
    protected $fillable = [
        'mandatory_task_id',
        'user_id',
        'answers',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function mandatoryTask()
    {
        return $this->belongsTo(MandatoryTask::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}