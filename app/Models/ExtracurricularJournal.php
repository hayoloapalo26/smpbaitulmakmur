<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtracurricularJournal extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
        'verified_at' => 'datetime',
    ];

    public function extracurricular()
    {
        return $this->belongsTo(Extracurricular::class, 'extracurricular_id');
    }

    public function pembina()
    {
        return $this->belongsTo(User::class, 'pembina_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function attendances()
    {
        return $this->hasMany(ExtracurricularAttendance::class, 'extracurricular_journal_id');
    }

    public function isVerified(): bool
    {
        return $this->status_verifikasi === 'diterima';
    }
}
