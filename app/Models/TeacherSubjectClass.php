<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherSubjectClass extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::saving(function (TeacherSubjectClass $tsc) {
            if ($tsc->subject_id && $tsc->classroom_id) {
                $query = static::withoutGlobalScopes()
                    ->where('subject_id', $tsc->subject_id)
                    ->where('classroom_id', $tsc->classroom_id);

                if ($tsc->id) {
                    $query->where('id', '!=', $tsc->id);
                }

                $existing = $query->with(['teacher', 'subject', 'classroom'])->first();
                if ($existing) {
                    $teacherName = $existing->teacher?->name ?? 'Guru Lain';
                    $subjectName = $existing->subject?->name ?? 'Mata pelajaran ini';
                    $className = $existing->classroom?->name ?? 'kelas terpilih';
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'classroom_id' => "Mata pelajaran {$subjectName} di {$className} sudah memiliki guru pengampu ({$teacherName}). Sistem menolak penugasan dobel.",
                    ]);
                }
            }
        });
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }
}
