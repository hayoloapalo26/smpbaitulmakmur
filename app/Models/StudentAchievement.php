<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAchievement extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::saving(function ($model) {
            if (!empty($model->student_id)) {
                $student = Student::withoutGlobalScopes()->find($model->student_id);
                if ($student && !empty($student->institution_id)) {
                    $model->institution_id = $student->institution_id;
                }
            }
            if (empty($model->input_by) && auth()->check()) {
                $model->input_by = auth()->id();
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function inputBy()
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function getKategoriLabelAttribute(): string
    {
        return match ($this->kategori) {
            'akademik' => 'Prestasi Akademik',
            'non_akademik' => 'Prestasi Non-Akademik',
            'religius' => 'Prestasi Religius (Keagamaan)',
            default => ucwords(str_replace('_', ' ', $this->kategori ?? 'Non-Akademik')),
        };
    }
}

