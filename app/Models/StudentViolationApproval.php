<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentViolationApproval extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'acc_piket'             => 'boolean',
        'acc_piket_at'          => 'datetime',
        'acc_wali_kelas'        => 'boolean',
        'acc_wali_kelas_at'     => 'datetime',
        'acc_bk'                => 'boolean',
        'acc_bk_at'             => 'datetime',
        'acc_waka_kesiswaan'    => 'boolean',
        'acc_waka_kesiswaan_at' => 'datetime',
        'acc_kepala_sekolah'    => 'boolean',
        'acc_kepala_sekolah_at' => 'datetime',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function piketUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acc_piket_by');
    }

    public function waliKelasUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acc_wali_kelas_by');
    }

    public function bkUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acc_bk_by');
    }

    public function kesiswaanUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acc_waka_kesiswaan_by');
    }

    public function kepsekUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acc_kepala_sekolah_by');
    }

    /**
     * Helper untuk mengambil atau menginisialisasi rekaman approval
     */
    public static function getApproval(?int $classroomId, string $periodeType, string $periodeKey): self
    {
        return static::firstOrCreate([
            'classroom_id' => $classroomId,
            'periode_type' => $periodeType,
            'periode_key'  => $periodeKey,
        ]);
    }
}
