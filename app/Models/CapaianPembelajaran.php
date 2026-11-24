<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapaianPembelajaran extends Model
{
    use \App\Models\Traits\BelongsToInstitution;
    use HasFactory;

    protected $table = 'capaian_pembelajarans';

    protected $guarded = ['id'];

    public const FASE_OPTIONS = [
        'A' => 'Fase A (Kelas 1 - 2 SD/MI)',
        'B' => 'Fase B (Kelas 3 - 4 SD/MI)',
        'C' => 'Fase C (Kelas 5 - 6 SD/MI)',
        'D' => 'Fase D (Kelas 7 - 9 SMP/MTs)',
        'E' => 'Fase E (Kelas 10 SMA/MA/SMK)',
        'F' => 'Fase F (Kelas 11 - 12 SMA/MA/SMK)',
    ];

    protected $casts = [
        'fases' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function ($cp) {
            if (!empty($cp->fases) && is_array($cp->fases)) {
                $cp->fases = array_values(array_unique(array_filter($cp->fases)));
                $faseNames = array_map(fn ($f) => "Fase {$f}", $cp->fases);
                $cp->fase = implode(', ', $faseNames);
            } elseif (empty($cp->fases) && !empty($cp->fase)) {
                $cp->fases = ['D'];
            }
        });

        static::creating(function ($cp) {
            if (empty($cp->kode_cp)) {
                $query = static::where('guru_id', $cp->guru_id)
                    ->where('subject_id', $cp->subject_id);

                if (!empty($cp->tingkat)) {
                    $query->where('tingkat', $cp->tingkat);
                }

                $count = $query->count() + 1;
                $cp->kode_cp = (string) $count;
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'published')
              ->orWhereNull('status');
        });
    }

    public function isPublished(): bool
    {
        return ($this->status ?? 'published') === 'published';
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function tujuanPembelajarans()
    {
        return $this->hasMany(TujuanPembelajaran::class, 'capaian_pembelajaran_id');
    }

    public function rppms()
    {
        return $this->hasMany(Rppm::class, 'capaian_pembelajaran_id');
    }

    public function getDisplayTitleAttribute(): string
    {
        $kode = $this->kode_cp ? "[CP {$this->kode_cp}] " : '';
        $faseStr = !empty($this->fases) ? 'Fase ' . implode('&', (array) $this->fases) : ($this->fase ?: 'Fase D');
        $tingkatStr = $this->tingkat ? "Kelas {$this->tingkat} - " : '';
        return "{$kode}[{$faseStr} | {$tingkatStr}{$this->elemen}] " . \Illuminate\Support\Str::limit($this->deskripsi_cp, 60);
    }
}
