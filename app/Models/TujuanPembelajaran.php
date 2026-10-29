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
}
