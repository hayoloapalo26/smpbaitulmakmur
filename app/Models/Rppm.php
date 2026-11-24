<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rppm extends Model
{
    use \App\Models\Traits\BelongsToInstitution;
    use HasFactory;

    protected $table = 'rppms';

    protected $fillable = [
        'institution_id',
        'guru_id',
        'kelas_fase',
        'fase',
        'fases',
        'mata_pelajaran',
        'capaian_pembelajaran_id',
        'alur_tujuan_pembelajaran_id',
        'selected_tp_ids',
        'status',
        'catatan_kepala_sekolah',
        'reviewed_by',
        'reviewed_at',
        'topik',
        'alokasi_waktu',
        'link_drive',
        'content',
    ];

    protected $casts = [
        'content'         => 'array',
        'selected_tp_ids' => 'array',
        'fases'           => 'array',
        'reviewed_at'     => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($rppm) {
            if (!empty($rppm->fases) && is_array($rppm->fases)) {
                $rppm->fases = array_values(array_unique(array_filter($rppm->fases)));
                $faseNames = array_map(fn ($f) => "Fase {$f}", $rppm->fases);
                $rppm->fase = implode(', ', $faseNames);
            } elseif (empty($rppm->fases) && !empty($rppm->fase)) {
                $rppm->fases = ['D'];
            }

            if (empty($rppm->kelas_fase)) {
                $faseLabel = $rppm->fase ?: 'Fase D';
                $rppm->kelas_fase = "Kelas 7 / {$faseLabel}";
            }
        });
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function capaianPembelajaran()
    {
        return $this->belongsTo(CapaianPembelajaran::class, 'capaian_pembelajaran_id');
    }

    public function alurTujuanPembelajaran()
    {
        return $this->belongsTo(AlurTujuanPembelajaran::class, 'alur_tujuan_pembelajaran_id');
    }

    public function getSelectedTpsAttribute()
    {
        if (empty($this->selected_tp_ids)) {
            return collect();
        }

        return TujuanPembelajaran::whereIn('id', $this->selected_tp_ids)->get();
    }

    public function getLinkDriveAttribute($value): ?string
    {
        if (!empty($value)) {
            return $value;
        }

        if (is_array($this->content) && !empty($this->content['link_drive'])) {
            return $this->content['link_drive'];
        }

        return null;
    }
}
