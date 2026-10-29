<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuranAchievement extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'type' => 'array',
        'verified_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getProgramLabelAttribute(): string
    {
        return match ($this->program) {
            'yanbua'           => 'Yanbu\'a',
            'tahsin'           => 'Tahsin',
            'tahfidz_dasar'    => 'Tahfidz Dasar',
            'tahfidz_lanjutan' => 'Tahfidz Lanjutan',
            'tahfidz'          => 'Tahfidz',
            default            => ucfirst(str_replace('_', ' ', (string)($this->program ?? 'Tahfidz'))),
        };
    }
}
