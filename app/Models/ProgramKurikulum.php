<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramKurikulum extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    use HasFactory;

    protected $table = 'program_kurikulums';

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
        'status_persetujuan',
        'catatan_kepsek',
        'tanggal_persetujuan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
