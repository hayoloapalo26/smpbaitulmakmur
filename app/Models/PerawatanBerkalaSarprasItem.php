<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerawatanBerkalaSarprasItem extends Model
{
    use HasFactory;

    protected $table = 'perawatan_berkala_sarpras_items';

    protected $guarded = ['id'];

    protected $casts = [
        'jumlah_unit' => 'integer',
        'biaya' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saved(function ($item) {
            $item->perawatan?->recalculateTotals();

            // Opsional: Perbarui kondisi master inventaris jika kondisi setelah perawatan berubah menjadi Baik
            if ($item->inventory && !empty($item->kondisi_setelah)) {
                $inventory = $item->inventory;
                if ($item->kondisi_setelah === 'Baik' && $inventory->kondisi !== 'Baik') {
                    $inventory->updateQuietly([
                        'kondisi' => 'Baik',
                    ]);
                } elseif ($item->kondisi_setelah === 'Rusak Berat') {
                    $inventory->updateQuietly([
                        'kondisi' => 'Rusak Berat',
                    ]);
                }
            }
        });

        static::deleted(function ($item) {
            $item->perawatan?->recalculateTotals();
        });
    }

    /**
     * Laporan induk perawatan berkala
     */
    public function perawatan(): BelongsTo
    {
        return $this->belongsTo(PerawatanBerkalaSarpras::class, 'perawatan_berkala_id');
    }

    /**
     * Master aset sarpras / inventaris yang diperiksa
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
