<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolAccreditation extends Model
{
    use HasFactory;
    use BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'masa_berlaku_sampai_tahun' => 'integer',
        'nilai_angka' => 'integer',
        'tanggal_sk' => 'date',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            // Sinkronisasi kolom 'no' dan 'nomor_akreditasi'
            if (!empty($model->nomor_akreditasi) && empty($model->no)) {
                $model->no = $model->nomor_akreditasi;
            } elseif (!empty($model->no) && empty($model->nomor_akreditasi)) {
                $model->nomor_akreditasi = $model->no;
            }

            // Otomatis assign user_id jika belum terisi
            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }

            // Otomatis cek status kedaluwarsa jika status masih Aktif
            if ($model->status === 'Aktif' && !empty($model->masa_berlaku_sampai_tahun)) {
                if ((int) $model->masa_berlaku_sampai_tahun < (int) date('Y')) {
                    $model->status = 'Kedaluwarsa';
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    // Accessor / Mutator untuk alias 'no'
    public function getNoAttribute($value)
    {
        return $value ?: $this->nomor_akreditasi;
    }

    public function setNoAttribute($value)
    {
        $this->attributes['no'] = $value;
        if (empty($this->attributes['nomor_akreditasi'])) {
            $this->attributes['nomor_akreditasi'] = $value;
        }
    }
}
