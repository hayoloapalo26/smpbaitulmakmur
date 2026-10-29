<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlurTujuanPembelajaran extends Model
{
    use \App\Models\Traits\BelongsToInstitution;
    use HasFactory;

    protected $table = 'alur_tujuan_pembelajarans';

    protected $guarded = ['id'];

    protected $casts = [
        'tp_ids'      => 'array',
        'urutan_alur' => 'array',
    ];

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function rppms()
    {
        return $this->hasMany(Rppm::class, 'alur_tujuan_pembelajaran_id');
    }

    /**
     * Mengambil koleksi TujuanPembelajaran yang diakumulasikan
     */
    public function getAccumulatedTpsAttribute()
    {
        if (empty($this->tp_ids)) {
            return collect();
        }

        return TujuanPembelajaran::whereIn('id', $this->tp_ids)
            ->get()
            ->sortBy(function ($tp) {
                return array_search($tp->id, $this->tp_ids);
            });
    }
}
