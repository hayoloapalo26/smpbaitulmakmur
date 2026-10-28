<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BkWorkProgram extends Model
{
    use \App\Models\Traits\BelongsToInstitution;
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_persetujuan' => 'datetime',
        'estimasi_anggaran' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
