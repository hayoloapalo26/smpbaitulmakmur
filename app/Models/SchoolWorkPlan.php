<?php

namespace App\Models;

use App\Models\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolWorkPlan extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $fillable = [
        'institution_id',
        'document_type',
        'drive_link',
        'status',
        'ketua_note',
        'pembina_note',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new InstitutionScope);
        
        static::creating(function ($model) {
            if (empty($model->institution_id) && auth()->hasUser()) {
                $model->institution_id = auth()->user()->institution_id;
            }
        });
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
