<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HarmonisasiInternal extends Model
{
    use HasFactory, BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
        'tanggal_persetujuan' => 'datetime',
    ];

    /**
     * User (Staf Humas) yang membuat pengajuan kegiatan harmonisasi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Kepala Sekolah yang menyetujui / memberikan respon
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Lembaga / Unit Sekolah
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
