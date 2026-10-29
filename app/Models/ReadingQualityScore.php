<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadingQualityScore extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
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

    public function getPredikatAttribute(): string
    {
        if (!empty($this->attributes['predikat'])) {
            return $this->attributes['predikat'];
        }
        return Grade::determineScale($this->scale_score)['predikat'];
    }

    public function getPredikatColorAttribute(): string
    {
        return Grade::determineScale($this->scale_score)['color'];
    }

    public function getProgramLabelAttribute(): string
    {
        return match ($this->program) {
            'yanbua'           => 'Yanbu\'a',
            'tahsin'           => 'Tahsin',
            'tahfidz_dasar'    => 'Tahfidz Dasar',
            'tahfidz_lanjutan' => 'Tahfidz Lanjutan',
            'tahfidz'          => 'Tahfidz',
            default            => ucfirst(str_replace('_', ' ', (string)$this->program)),
        };
    }
}
