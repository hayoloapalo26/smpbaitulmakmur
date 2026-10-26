<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramKesiswaan extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    use HasFactory;

    protected $fillable = [
        'user_id',
        'tahun_ajaran',
        'nama_program',
        'kategori',
        'tipe_pelaksanaan',
        'sifat',
        'tujuan',
        'teknis_pelaksanaan',
        'sasaran',
        'waktu_pelaksanaan',
        'dokumen_terkait',
        'estimasi_anggaran',
        'status_pelaksanaan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
