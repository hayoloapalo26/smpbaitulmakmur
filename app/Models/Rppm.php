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
        'topik',
        'alokasi_waktu',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}
