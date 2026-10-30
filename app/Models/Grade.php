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

    public function capaianPembelajaran()
    {
        return $this->belongsTo(CapaianPembelajaran::class, 'capaian_pembelajaran_id');
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

    public const SCALE_EXCELLENT        = 'Excellent';
    public const SCALE_SANGAT_BAIK     = 'Sangat Baik';
    public const SCALE_BAIK            = 'Baik';
    public const SCALE_CUKUP_BAIK      = 'Cukup Baik';
    public const SCALE_PERLU_PEMBINAAN = 'Perlu Pembinaan';

    public static function determineScale(?float $score): array
    {
        if ($score === null) {
            return [
                'predikat'  => '-',
                'label'     => '-',
                'color'     => 'gray',
                'is_tuntas' => false,
            ];
        }

        $score = (float)$score;

        if ($score >= 100) {
            return [
                'predikat'  => self::SCALE_EXCELLENT,
                'label'     => '100 : Excellent',
                'color'     => 'amber',
                'is_tuntas' => true,
            ];
        }

        if ($score >= 90) {
            return [
                'predikat'  => self::SCALE_SANGAT_BAIK,
                'label'     => '90 - 99 : Sangat Baik',
                'color'     => 'success',
                'is_tuntas' => true,
            ];
        }

        if ($score >= 80) {
            return [
                'predikat'  => self::SCALE_BAIK,
                'label'     => '80 - 89 : Baik',
                'color'     => 'info',
                'is_tuntas' => true,
            ];
        }

        if ($score >= 70) {
            return [
                'predikat'  => self::SCALE_CUKUP_BAIK,
                'label'     => '70 - 79 : Cukup Baik',
                'color'     => 'warning',
                'is_tuntas' => true,
            ];
        }

        return [
            'predikat'  => self::SCALE_PERLU_PEMBINAAN,
            'label'     => '< 70 : Perlu Pembinaan',
            'color'     => 'danger',
            'is_tuntas' => false,
        ];
    }

    public function isTuntas(): bool
    {
        if ($this->is_remedial && $this->nilai_remedial !== null) {
            return (float)$this->nilai_remedial >= 70;
        }

        return (float)($this->score ?? 0) >= 70;
    }

    public function getNilaiAkhirAttribute(): float
    {
        if ($this->is_remedial && $this->nilai_remedial !== null) {
            return (float)$this->nilai_remedial;
        }

        return (float)($this->score ?? 0);
    }

    public function getPredikatAttribute(): string
    {
        return static::determineScale($this->nilai_akhir)['predikat'];
    }

    public function getPredikatLabelAttribute(): string
    {
        return static::determineScale($this->nilai_akhir)['label'];
    }

    public function getPredikatColorAttribute(): string
    {
        return static::determineScale($this->nilai_akhir)['color'];
    }

    public function getSelectedTpsAttribute()
    {
        if (empty($this->tp_ids)) {
            return collect();
        }

        return TujuanPembelajaran::whereIn('id', $this->tp_ids)->get();
    }
}
