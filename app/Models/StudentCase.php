<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StudentCase extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'kategori_kasus'             => 'array',
        'tanggal_kejadian'           => 'date',
        'recommended_to_bk'         => 'boolean',
        'recommended_to_kesiswaan'  => 'boolean',
        'recommended_to_kepsek'     => 'boolean',
        'diteruskan_ke_bk_at'        => 'datetime',
        'dikembalikan_dari_bk_at'    => 'datetime',
        'diteruskan_ke_kesiswaan_at' => 'datetime',
        'diteruskan_ke_kepsek_at'    => 'datetime',
        'diselesaikan_at'            => 'datetime',
    ];

    protected static function booted(): void
    {
        // Auto-generate nomor kasus saat dibuat
        static::creating(function (self $model) {
            if (empty($model->nomor_kasus)) {
                $year  = now()->format('Y');
                $count = self::whereYear('created_at', $year)->count() + 1;
                $model->nomor_kasus = 'KSS-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
            }
            if (empty($model->ditangani_oleh)) {
                $model->ditangani_oleh = 'wali_kelas';
            }
        });
    }

    // ─── Relasi ────────────────────────────────────────────────────
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function waliKelas()
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    public function diselesaikanOleh()
    {
        return $this->belongsTo(User::class, 'diselesaikan_oleh');
    }

    public function parentSummons()
    {
        return $this->hasMany(ParentSummon::class, 'student_case_id');
    }

    // ─── Helper status ─────────────────────────────────────────────
    public function getLevelHandlerLabel(): string
    {
        return match($this->ditangani_oleh) {
            'bk'             => 'Guru BK',
            'kesiswaan'      => 'Waka Kesiswaan',
            'kepala_sekolah' => 'Kepala Sekolah',
            default          => 'Wali Kelas',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'selesai'  => 'success',
            'proses'   => 'warning',
            default    => 'gray',
        };
    }
}
