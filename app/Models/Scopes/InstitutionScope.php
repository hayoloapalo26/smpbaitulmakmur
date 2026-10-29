<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class InstitutionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->hasUser() && !auth()->user()->hasRole('superadmin')) {
            $user = auth()->user();
            if ($user->institution_id == 1) {
                return; // Yayasan can see all
            }

            $table = $model->getTable();
            $builder->where(function ($q) use ($table, $user) {
                $q->where($table . '.institution_id', $user->institution_id)
                  ->orWhereNull($table . '.institution_id');

                // Dukungan Guru Lintas Lembaga:
                // Izinkan guru mengakses rombel kelas yang ditugaskan / dijadwalkan dia ajar
                if ($table === 'classrooms') {
                    $q->orWhereIn($table . '.id', function ($sub) use ($user) {
                        $sub->select('classroom_id')
                            ->from('schedules')
                            ->where('teacher_id', $user->id);
                    });
                    $q->orWhereIn($table . '.id', function ($sub) use ($user) {
                        $sub->select('classroom_id')
                            ->from('teacher_subject_classes')
                            ->where('teacher_id', $user->id);
                    });
                }
            });
        }
    }
}