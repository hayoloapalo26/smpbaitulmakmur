<?php

namespace App\Models;

use App\Models\Scopes\InstitutionScope;
use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrincipalTeacherEvaluation extends Model
{
    use BelongsToInstitution;

    protected $table = 'principal_teacher_evaluations';

    protected $guarded = ['id'];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
        'tanggal_penilaian' => 'date',
        'score' => 'float',
        'aspek_penilaian' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new InstitutionScope);

        static::saving(function (PrincipalTeacherEvaluation $evaluation) {
            if ($evaluation->score !== null && empty($evaluation->predikat)) {
                $evaluation->predikat = static::determinePredicate((float) $evaluation->score);
            }
        });
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public static function determinePredicate(float $score): string
    {
        if ($score >= 90) {
            return 'Sangat Baik (A)';
        }
        if ($score >= 80) {
            return 'Baik (B)';
        }
        if ($score >= 70) {
            return 'Cukup (C)';
        }
        return 'Kurang (D)';
    }

    public static function getPredicateBadgeColor(float $score): string
    {
        if ($score >= 90) {
            return 'success';
        }
        if ($score >= 80) {
            return 'primary';
        }
        if ($score >= 70) {
            return 'warning';
        }
        return 'danger';
    }

    public static function getNamaBulan(int $bulan): string
    {
        $bulanMap = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $bulanMap[$bulan] ?? "Bulan {$bulan}";
    }

    public function getPeriodeBulanTahunAttribute(): string
    {
        return static::getNamaBulan($this->bulan) . ' ' . $this->tahun;
    }
}
