<?php

namespace App\Models\Traits;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToInstitution
{
    public static function bootBelongsToInstitution()
    {
        // Global Scope for Reading Data: each lembaga only sees its own institution_id
        static::addGlobalScope('institution', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();

                // If user is Yayasan (ID = 1) or Superadmin, they can see everything (no filter applied)
                if (!$user->hasRole('superadmin') && $user->institution_id != 1) {
                    $builder->where($builder->getModel()->getTable() . '.institution_id', $user->institution_id);
                }
            }
        });

        // Event for Creating & Saving Data: always ensure records match institution_id
        static::saving(function ($model) {
            if (!\Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), 'institution_id')) {
                return;
            }

            if (!empty($model->student_id)) {
                $studentInstId = \App\Models\Student::withoutGlobalScopes()
                    ->where('id', $model->student_id)
                    ->value('institution_id');
                if ($studentInstId) {
                    $model->institution_id = $studentInstId;
                    return;
                }
            }

            if (empty($model->institution_id)) {
                if (!empty($model->classroom_id)) {
                    $classroomInstId = \App\Models\Classroom::withoutGlobalScopes()
                        ->where('id', $model->classroom_id)
                        ->value('institution_id');
                    if ($classroomInstId) {
                        $model->institution_id = $classroomInstId;
                        return;
                    }
                }

                if (auth()->check() && !empty(auth()->user()->institution_id)) {
                    $model->institution_id = auth()->user()->institution_id;
                }
            }
        });
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
