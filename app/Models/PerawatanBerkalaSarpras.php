<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerawatanBerkalaSarpras extends Model
{
    use HasFactory, BelongsToInstitution;

    protected $table = 'perawatan_berkala_sarpras';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_persetujuan' => 'datetime',
        'periode_tahun' => 'integer',
        'total_item_diperiksa' => 'integer',
        'total_unit_diperiksa' => 'integer',
        'total_kondisi_baik' => 'integer',
        'total_kondisi_rusak_ringan' => 'integer',
        'total_kondisi_rusak_berat' => 'integer',
        'total_biaya_pemeliharaan' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->nomor_laporan)) {
                $year = date('Y');
                $month = date('m');
                $count = static::withoutGlobalScopes()
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $seq = str_pad($count, 4, '0', STR_PAD_LEFT);
                $model->nomor_laporan = "RWT/{$year}/{$month}/{$seq}";
            }

            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }

            if (empty($model->periode_tahun)) {
                $model->periode_tahun = (int) date('Y');
            }

            if (empty($model->periode_bulan)) {
                $bulanMap = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                $model->periode_bulan = $bulanMap[(int) date('n')] ?? 'Januari';
            }
        });
    }

    /**
     * Hitung ulang rekapitulasi jumlah item, unit fisik, kondisi akhir, dan total biaya pemeliharaan
     */
    public function recalculateTotals(): void
    {
        $totalItems = $this->items()->count();
        $totalUnit = (int) $this->items()->sum('jumlah_unit');
        $totalBiaya = (float) $this->items()->sum('biaya');

        $kondisiBaik = (int) $this->items()->where('kondisi_setelah', 'Baik')->sum('jumlah_unit');
        $kondisiRusakRingan = (int) $this->items()->where('kondisi_setelah', 'Rusak Ringan')->sum('jumlah_unit');
        $kondisiRusakBerat = (int) $this->items()->where('kondisi_setelah', 'Rusak Berat')->sum('jumlah_unit');

        $this->updateQuietly([
            'total_item_diperiksa' => $totalItems,
            'total_unit_diperiksa' => $totalUnit,
            'total_biaya_pemeliharaan' => $totalBiaya,
            'total_kondisi_baik' => $kondisiBaik,
            'total_kondisi_rusak_ringan' => $kondisiRusakRingan,
            'total_kondisi_rusak_berat' => $kondisiRusakBerat,
        ]);
    }

    /**
     * Rincian aset sarana dan inventaris yang dirawat
     */
    public function items(): HasMany
    {
        return $this->hasMany(PerawatanBerkalaSarprasItem::class, 'perawatan_berkala_id');
    }

    /**
     * Staf sarpras / petugas pencatat laporan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Kepala Sekolah yang meninjau / menyetujui laporan perawatan
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Lokasi ruangan spesifik yang dirawat (jika ada)
     */
    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PenempatanSarpras::class, 'penempatan_sarpras_id');
    }

    /**
     * Lembaga / Unit Sekolah
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
