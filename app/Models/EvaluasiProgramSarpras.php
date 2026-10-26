<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluasiProgramSarpras extends Model
{
    use HasFactory, BelongsToInstitution;

    protected $table = 'evaluasi_program_sarpras';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_evaluasi' => 'date',
        'tanggal_review' => 'datetime',
        'persentase_ketercapaian' => 'integer',
        'estimasi_anggaran' => 'decimal:2',
        'realisasi_anggaran' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->nomor_evaluasi)) {
                $year = date('Y');
                $month = date('m');
                $count = static::withoutGlobalScopes()
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $seq = str_pad($count, 4, '0', STR_PAD_LEFT);
                $model->nomor_evaluasi = "EVAL-SARPRAS/{$year}/{$month}/{$seq}";
            }

            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }

            if (empty($model->tanggal_evaluasi)) {
                $model->tanggal_evaluasi = now()->toDateString();
            }
        });

        static::saving(function ($model) {
            // Tentukan predikat pencapaian otomatis berdasarkan persentase
            $pct = (int) ($model->persentase_ketercapaian ?? 100);
            if ($pct >= 90) {
                $model->predikat_capaian = 'Sangat Baik';
            } elseif ($pct >= 75) {
                $model->predikat_capaian = 'Baik';
            } elseif ($pct >= 60) {
                $model->predikat_capaian = 'Cukup';
            } else {
                $model->predikat_capaian = 'Kurang';
            }
        });
    }

    /**
     * Program Kerja Sarpras yang dievaluasi
     */
    public function programSarpras(): BelongsTo
    {
        return $this->belongsTo(ProgramSarpras::class, 'program_sarpras_id');
    }

    /**
     * Staf Sarpras yang menyusun evaluasi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Kepala Sekolah yang meninjau (review) hasil evaluasi
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Lembaga / Unit Sekolah
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
