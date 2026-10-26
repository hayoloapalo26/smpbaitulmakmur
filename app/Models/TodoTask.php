<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoTask extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'target_roles' => 'array',
        'target_users' => 'array',
        'due_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completions()
    {
        return $this->hasMany(TodoCompletion::class);
    }
}