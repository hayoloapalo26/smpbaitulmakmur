<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningPlan extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    use HasFactory;

    protected $fillable = [
        'penyusun_id',
        'penelaah_id',
        'mata_pelajaran',
        'unit_kerja_penyusun',
        'unit_kerja_penelaah',
        'evaluations',
        'average_score',
        'category',
    ];

    protected $casts = [
        'evaluations' => 'array',
        'average_score' => 'decimal:2',
    ];

    public function penyusun()
    {
        return $this->belongsTo(User::class, 'penyusun_id');
    }

    public function penelaah()
    {
        return $this->belongsTo(User::class, 'penelaah_id');
    }

    /**
     * Hitung rata-rata skor dan tentukan kategori berdasarkan JSON evaluations
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (is_array($model->evaluations)) {
                $total = 0;
                $count = 12; // 12 aspek penilaian

                for ($i = 1; $i <= 12; $i++) {
                    $score = $model->evaluations["aspect_{$i}_score"] ?? 0;
                    $total += (int) $score;
                }

                $average = $total / $count;
                $model->average_score = $average;

                if ($average >= 3.26) {
                    $model->category = 'Sangat Baik';
                } elseif ($average >= 2.51) {
                    $model->category = 'Baik';
                } elseif ($average >= 1.76) {
                    $model->category = 'Cukup';
                } else {
                    $model->category = 'Kurang';
                }
            }
        });
    }
}
