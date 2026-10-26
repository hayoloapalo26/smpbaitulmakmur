<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RencanaKebutuhanSarprasItem extends Model
{
    use HasFactory;

    protected $table = 'rencana_kebutuhan_sarpras_items';

    protected $guarded = ['id'];

    protected $casts = [
        'jumlah' => 'integer',
        'estimasi_harga_satuan' => 'decimal:2',
        'subtotal_biaya' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($item) {
            $jumlah = $item->jumlah ?? 1;
            $harga = $item->estimasi_harga_satuan ?? 0;
            $item->subtotal_biaya = $jumlah * $harga;
        });

        static::saved(function ($item) {
            if ($item->rencanaKebutuhanSarpras) {
                $item->rencanaKebutuhanSarpras->recalculateTotals();
            }
        });

        static::deleted(function ($item) {
            if ($item->rencanaKebutuhanSarpras) {
                $item->rencanaKebutuhanSarpras->recalculateTotals();
            }
        });
    }

    /**
     * Rencana kebutuhan sarpras induk
     */
    public function rencanaKebutuhanSarpras(): BelongsTo
    {
        return $this->belongsTo(RencanaKebutuhanSarpras::class, 'rencana_kebutuhan_sarpras_id');
    }
}
