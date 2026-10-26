<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolVisionMission extends Model
{
    use HasFactory;
    use BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'misi' => 'array',
        'tujuan' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            // Otomatis assign user_id jika login
            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }

            // Normalisasi array misi jika berupa string
            if (is_string($model->misi)) {
                $decoded = json_decode($model->misi, true);
                $model->misi = is_array($decoded) ? $decoded : [['poin' => $model->misi]];
            }

            // Normalisasi array tujuan jika berupa string
            if (is_string($model->tujuan)) {
                $decoded = json_decode($model->tujuan, true);
                $model->tujuan = is_array($decoded) ? $decoded : [['kategori' => 'Tujuan Umum', 'poin' => $model->tujuan]];
            }

            // Jika status Aktif, non-aktifkan yang lain untuk lembaga yang sama agar hanya 1 yang aktif utama
            if ($model->status === 'Aktif') {
                static::withoutGlobalScopes()
                    ->where('institution_id', $model->institution_id)
                    ->where('id', '!=', $model->id ?? 0)
                    ->where('status', 'Aktif')
                    ->update(['status' => 'Arsip']);
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

    public static function getActive(?int $institutionId = null): ?self
    {
        $query = static::where('status', 'Aktif');
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        return $query->latest()->first() ?? static::latest()->first();
    }
}
