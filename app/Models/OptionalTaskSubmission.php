<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionalTaskSubmission extends Model
{
    protected $fillable = [
        'optional_task_id',
        'user_id',
        'answers',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function optionalTask()
    {
        return $this->belongsTo(OptionalTask::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}