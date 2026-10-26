<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramSarpras extends Model
{
    use \App\Models\Traits\BelongsToInstitution;
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_persetujuan' => 'datetime',
        'estimasi_anggaran' => 'decimal:2',
    ];

    /**
     * Staf Sarpras penyusun program
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kepala Sekolah yang menyetujui (ACC) program kerja
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Laporan evaluasi atas program kerja ini
     */
    public function evaluasi(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EvaluasiProgramSarpras::class, 'program_sarpras_id');
    }
}
