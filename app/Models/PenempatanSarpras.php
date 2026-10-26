<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenempatanSarpras extends Model
{
    use HasFactory, BelongsToInstitution;

    protected $table = 'penempatan_sarpras';

    protected $guarded = ['id'];

    protected $casts = [
        'total_item_sarpras' => 'integer',
        'total_unit_sarpras' => 'integer',
        'total_nilai_aset' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->kode_ruangan)) {
                $prefix = match ($model->kategori_ruangan) {
                    'Ruang Kelas' => 'R-KLS',
                    'Laboratorium / Praktik' => 'R-LAB',
                    'Ruang Pendidik / Guru' => 'R-GURU',
                    'Ruang Pimpinan & TU' => 'R-PIMP',
                    'Perpustakaan' => 'R-PUS',
                    'Tempat Ibadah / Masjid' => 'R-MSJ',
                    'Ruang UKS' => 'R-UKS',
                    'Gudang & Logistik' => 'R-GDG',
                    default => 'RNG',
                };
                $count = static::withoutGlobalScopes()
                    ->where('kategori_ruangan', $model->kategori_ruangan)
                    ->count() + 1;
                $seq = str_pad($count, 3, '0', STR_PAD_LEFT);
                $model->kode_ruangan = "{$prefix}-{$seq}";
            }

            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }

    /**
     * Hitung ulang rekapitulasi jumlah ragam barang, unit fisik, dan nilai aset
     */
    public function recalculateTotals(): void
    {
        $totalItems = $this->items()->count();
        $totalUnit = (int) $this->items()->sum('jumlah');
        $totalNilai = (float) $this->items()->sum('subtotal_nilai_aset');

        $this->updateQuietly([
            'total_item_sarpras' => $totalItems,
            'total_unit_sarpras' => $totalUnit,
            'total_nilai_aset' => $totalNilai,
        ]);
    }

    /**
     * Rincian barang yang ditempatkan di ruangan ini
     */
    public function items(): HasMany
    {
        return $this->hasMany(PenempatanSarprasItem::class, 'penempatan_sarpras_id');
    }

    /**
     * User guru / staf penanggung jawab ruangan
     */
    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab_id');
    }

    /**
     * Staf Sarpras pencatat data penempatan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Lembaga / Unit Sekolah
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
