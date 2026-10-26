<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenempatanSarprasItem extends Model
{
    use HasFactory;

    protected $table = 'penempatan_sarpras_items';

    protected $guarded = ['id'];

    protected $casts = [
        'jumlah' => 'integer',
        'subtotal_nilai_aset' => 'decimal:2',
        'tanggal_penempatan' => 'date',
    ];

    protected static function booted()
    {
        static::saving(function ($item) {
            if ($item->inventory) {
                $item->satuan = $item->inventory->satuan ?? 'Unit';
                $harga = (float) ($item->inventory->harga_perolehan ?? 0);
                $item->subtotal_nilai_aset = ((int) ($item->jumlah ?: 1)) * $harga;
            }
        });

        static::saved(function ($item) {
            if ($item->penempatanSarpras) {
                $item->penempatanSarpras->recalculateTotals();
            }
        });

        static::deleted(function ($item) {
            if ($item->penempatanSarpras) {
                $item->penempatanSarpras->recalculateTotals();
            }
        });
    }

    /**
     * Ruangan penempatan
     */
    public function penempatanSarpras(): BelongsTo
    {
        return $this->belongsTo(PenempatanSarpras::class, 'penempatan_sarpras_id');
    }

    /**
     * Master Inventaris Barang
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
