<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RencanaKebutuhanSarpras extends Model
{
    use HasFactory, BelongsToInstitution;

    protected $table = 'rencana_kebutuhan_sarpras';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_pengajuan' => 'date',
        'tanggal_persetujuan' => 'datetime',
        'total_estimasi_anggaran' => 'decimal:2',
        'total_item' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->nomor_rencana)) {
                $year = date('Y');
                $month = date('m');
                $count = static::withoutGlobalScopes()
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $seq = str_pad($count, 4, '0', STR_PAD_LEFT);
                $model->nomor_rencana = "RKB/{$year}/{$month}/{$seq}";
            }
        });
    }

    /**
     * Hitung ulang total anggaran dan total item kebutuhan
     */
    public function recalculateTotals(): void
    {
        $totalAnggaran = $this->items()->sum('subtotal_biaya');
        $totalItem = $this->items()->count();

        $this->updateQuietly([
            'total_estimasi_anggaran' => $totalAnggaran,
            'total_item' => $totalItem,
        ]);
    }

    /**
     * Daftar item sarana & prasarana yang dibutuhkan
     */
    public function items(): HasMany
    {
        return $this->hasMany(RencanaKebutuhanSarprasItem::class, 'rencana_kebutuhan_sarpras_id');
    }

    /**
     * Staf Sarpras yang menyusun rencana kebutuhan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Kepala Sekolah yang meninjau / menyetujui (ACC)
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
