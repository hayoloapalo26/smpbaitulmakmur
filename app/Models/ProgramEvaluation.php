<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramEvaluation extends Model
{
    use HasFactory;
    use BelongsToInstitution;

    protected $guarded = ['id'];

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function sourceProgram()
    {
        if ($this->program_source_type && class_exists($this->program_source_type) && $this->program_source_id) {
            return $this->belongsTo($this->program_source_type, 'program_source_id');
        }
        return null;
    }
}
