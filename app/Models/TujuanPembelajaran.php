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

    protected static function booted()
    {
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

    public function getDisplayTitleAttribute(): string
    {
        $prefix = $this->materi ? "[{$this->materi}] " : '';
        return "[{$this->kode_tp}] {$prefix}" . $this->deskripsi_tp;
    }
}
