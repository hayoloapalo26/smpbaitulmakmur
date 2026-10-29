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
}
