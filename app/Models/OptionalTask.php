<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\InstitutionScope;

class OptionalTask extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $fillable = [
        'institution_id',
        'title',
        'description',
        'target_roles',
        'form_schema',
        'reward_points',
        'is_active',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'form_schema' => 'array',
        'is_active' => 'boolean',
        'reward_points' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new InstitutionScope);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function submissions()
    {
        return $this->hasMany(OptionalTaskSubmission::class);
    }
}