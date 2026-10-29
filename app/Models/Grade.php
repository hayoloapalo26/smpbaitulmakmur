<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tp_ids'           => 'array',
        'tp_remedial_ids'  => 'array',
        'catatan_per_tp'   => 'array',
        'is_remedial'      => 'boolean',
        'is_pengayaan'     => 'boolean',
        'tanggal'          => 'date',
        'tanggal_remedial'  => 'date',
        'tanggal_pengayaan' => 'date',
        'score'            => 'float',
        'nilai_remedial'   => 'float',
        'nilai_pengayaan'  => 'float',
        'verified_at'      => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function rppm()
    {
        return $this->belongsTo(Rppm::class, 'rppm_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function tpPengayaan()
    {
        return $this->belongsTo(TujuanPembelajaran::class, 'tp_pengayaan_id');
    }

    public function isTuntas(): bool
    {
        if ($this->is_remedial && $this->nilai_remedial !== null) {
            return (float)$this->nilai_remedial >= 75;
        }

        return (float)($this->score ?? 0) >= 75;
    }

    public function getNilaiAkhirAttribute(): float
    {
        if ($this->is_remedial && $this->nilai_remedial !== null) {
            return (float)$this->nilai_remedial;
        }

        return (float)($this->score ?? 0);
    }

    public function getSelectedTpsAttribute()
    {
        if (empty($this->tp_ids)) {
            return collect();
        }

        return TujuanPembelajaran::whereIn('id', $this->tp_ids)->get();
    }
}
