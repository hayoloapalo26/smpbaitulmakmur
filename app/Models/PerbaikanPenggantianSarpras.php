<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerbaikanPenggantianSarpras extends Model
{
    use HasFactory, BelongsToInstitution;

    protected $table = 'perbaikan_penggantian_sarpras';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_laporan' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_persetujuan' => 'datetime',
        'jumlah_unit' => 'integer',
        'estimasi_biaya' => 'decimal:2',
        'realisasi_biaya' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->nomor_pengurusan)) {
                $year = date('Y');
                $month = date('m');
                $count = static::withoutGlobalScopes()
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $seq = str_pad($count, 4, '0', STR_PAD_LEFT);
                $model->nomor_pengurusan = "PPG/{$year}/{$month}/{$seq}";
            }

            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }

            if (empty($model->tanggal_laporan)) {
                $model->tanggal_laporan = now()->toDateString();
            }
        });

        static::saved(function ($model) {
            // Sinkronisasi status dan kondisi pada master inventaris
            if ($model->inventory) {
                $inventory = $model->inventory;
                if (in_array($model->status_proses, ['Selesai Diperbaiki', 'Selesai Diganti Unit Baru'])) {
                    $inventory->updateQuietly([
                        'kondisi' => 'Baik',
                        'status_inventaris' => 'Aktif / Digunakan',
                    ]);
                } elseif ($model->status_proses === 'Sedang Diperbaiki') {
                    $inventory->updateQuietly([
                        'status_inventaris' => 'Dalam Perbaikan / Servis',
                    ]);
                } elseif ($model->status_proses === 'Dihapuskan / Afkir') {
                    $inventory->updateQuietly([
                        'kondisi' => 'Rusak Berat',
                        'status_inventaris' => 'Diarsipkan / Afkir',
                    ]);
                }
            }
        });
    }

    /**
     * Master barang sarpras yang rusak atau hilang
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    /**
     * Ruangan asal penempatan barang (opsional)
     */
    public function penempatan(): BelongsTo
    {
        return $this->belongsTo(PenempatanSarpras::class, 'penempatan_sarpras_id');
    }

    /**
     * Staf Sarpras yang mengurus penanganan
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Kepala Sekolah yang menyetujui / memberikan disposisi
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
