<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TujuanPembelajaran extends Model
{
    use \App\Models\Traits\BelongsToInstitution;
    use HasFactory;

    protected $table = 'tujuan_pembelajarans';

    protected $guarded = ['id'];

    protected $casts = [
        'fases' => 'array',
        'rekomendasi_kelas' => 'array',
        'rekomendasi_tingkat' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function ($tp) {
            $cp = $tp->capaianPembelajaran ?? ($tp->capaian_pembelajaran_id ? CapaianPembelajaran::find($tp->capaian_pembelajaran_id) : null);

            if (!empty($tp->fases) && is_array($tp->fases)) {
                $tp->fases = array_values(array_unique(array_filter($tp->fases)));
            } elseif (empty($tp->fases)) {
                $tp->fases = !empty($cp?->fases) ? $cp->fases : ['D'];
            }

            if (!empty($tp->rekomendasi_kelas) && is_array($tp->rekomendasi_kelas)) {
                $tp->rekomendasi_kelas = array_values(array_unique(array_filter(array_map('intval', $tp->rekomendasi_kelas))));
            }

            if (!empty($tp->rekomendasi_tingkat) && is_array($tp->rekomendasi_tingkat)) {
                $tp->rekomendasi_tingkat = array_values(array_unique(array_filter(array_map('strval', $tp->rekomendasi_tingkat))));
            }
        });

        static::creating(function ($tp) {
            $cp = $tp->capaianPembelajaran ?? CapaianPembelajaran::find($tp->capaian_pembelajaran_id);
            $cpKode = $cp?->kode_cp ?: '1';

            if (empty($tp->kode_tp) || str_starts_with($tp->kode_tp, 'TP.')) {
                $count = static::where('capaian_pembelajaran_id', $tp->capaian_pembelajaran_id)->count() + 1;
                $tp->kode_tp = "{$cpKode}.{$count}";
            }

            if ($cp) {
                if (empty($tp->subject_id)) {
                    $tp->subject_id = $cp->subject_id;
                }
                if (empty($tp->kelas_tingkat)) {
                    $tp->kelas_tingkat = $cp->tingkat;
                }
            }
        });

        static::saved(function ($tp) {
            if ($tp->guru_id && $tp->subject_id) {
                AlurTujuanPembelajaran::syncForTeacherAndSubject(
                    $tp->guru_id,
                    $tp->subject_id,
                    $tp->kelas_tingkat ?? '7'
                );
            }
        });

        static::deleted(function ($tp) {
            if ($tp->guru_id && $tp->subject_id) {
                AlurTujuanPembelajaran::syncForTeacherAndSubject(
                    $tp->guru_id,
                    $tp->subject_id,
                    $tp->kelas_tingkat ?? '7'
                );
            }
        });
    }

    public function capaianPembelajaran()
    {
        return $this->belongsTo(CapaianPembelajaran::class, 'capaian_pembelajaran_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class, 'tp_pengayaan_id');
    }

    public function getRecommendedClassroomsAttribute()
    {
        if (empty($this->rekomendasi_kelas)) {
            return collect();
        }
        return Classroom::whereIn('id', (array) $this->rekomendasi_kelas)->get();
    }

    public function getDisplayTitleAttribute(): string
    {
        $faseStr = !empty($this->fases) ? '[Fase ' . implode('&', (array) $this->fases) . '] ' : '';
        $prefix = $this->materi ? "[{$this->materi}] " : '';
        return "{$faseStr}[{$this->kode_tp}] {$prefix}" . $this->deskripsi_tp;
    }
}
