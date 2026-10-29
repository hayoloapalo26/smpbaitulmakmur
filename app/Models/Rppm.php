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
        'guru_id',
        'kelas_fase',
        'mata_pelajaran',
        'capaian_pembelajaran_id',
        'alur_tujuan_pembelajaran_id',
        'selected_tp_ids',
        'status',
        'topik',
        'alokasi_waktu',
        'content',
    ];

    protected $casts = [
        'content'         => 'array',
        'selected_tp_ids' => 'array',
    ];

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
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
}
