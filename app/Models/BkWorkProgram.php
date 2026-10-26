<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BkWorkProgram extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    use HasFactory;

    protected $fillable = [
        'user_id',
        'tahun_ajaran',
        'judul_program',
        'bidang_layanan',
        'tipe_pelaksanaan',
        'sifat',
        'sasaran',
        'waktu_pelaksanaan',
        'tujuan',
        'teknis_pelaksanaan',
        'dokumen_terkait',
        'estimasi_anggaran',
        'status',
        'keterangan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
