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

    protected static function booted()
    {
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
        return "{$kode}[Kelas {$this->tingkat} - {$this->elemen}] " . \Illuminate\Support\Str::limit($this->deskripsi_cp, 60);
    }
}
