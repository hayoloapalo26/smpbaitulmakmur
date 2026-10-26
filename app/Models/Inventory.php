<?php

namespace App\Models;

use App\Models\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory, BelongsToInstitution;

    protected $table = 'inventories';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_perolehan' => 'date',
        'harga_perolehan' => 'decimal:2',
        'total_nilai_aset' => 'decimal:2',
        'jumlah' => 'integer',
        'jumlah_total' => 'integer',
        'jumlah_kondisi_baik' => 'integer',
        'jumlah_kondisi_rusak_ringan' => 'integer',
        'jumlah_kondisi_rusak_berat' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->kode_barang)) {
                $year = date('Y');
                $count = static::withoutGlobalScopes()
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $seq = str_pad($count, 4, '0', STR_PAD_LEFT);
                $model->kode_barang = "INV/{$year}/{$seq}";
            }

            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });

        static::saving(function ($model) {
            // Sinkronisasi jumlah dengan jumlah_total
            if (isset($model->jumlah_total)) {
                $model->jumlah = $model->jumlah_total;
            } elseif (isset($model->jumlah)) {
                $model->jumlah_total = $model->jumlah;
            } else {
                $model->jumlah = 1;
                $model->jumlah_total = 1;
            }

            // Isi tahun perolehan otomatis jika tanggal_perolehan ada
            if (!empty($model->tanggal_perolehan) && empty($model->tahun_perolehan)) {
                $model->tahun_perolehan = date('Y', strtotime($model->tanggal_perolehan));
            }

            // Hitung total nilai aset
            $qty = (int) ($model->jumlah_total ?: 1);
            $harga = (float) ($model->harga_perolehan ?: 0);
            $model->total_nilai_aset = $qty * $harga;

            // Tentukan status kondisi fisik umum jika belum dispesifikasikan
            $baik = (int) ($model->jumlah_kondisi_baik ?: 0);
            $ringan = (int) ($model->jumlah_kondisi_rusak_ringan ?: 0);
            $berat = (int) ($model->jumlah_kondisi_rusak_berat ?: 0);

            if ($berat > 0 && $baik === 0 && $ringan === 0) {
                $model->kondisi = 'Rusak Berat';
            } elseif ($ringan > 0 || $berat > 0) {
                $model->kondisi = 'Rusak Ringan';
            } else {
                $model->kondisi = 'Baik';
            }
        });
    }

    /**
     * Staf Sarpras yang mencatat / menginput inventaris
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Lembaga / Unit Sekolah pemilik aset
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    /**
     * Rincian penempatan barang ini di berbagai ruangan/kelas
     */
    public function penempatanItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PenempatanSarprasItem::class, 'inventory_id');
    }

    /**
     * Riwayat pemeriksaan dan perawatan berkala barang ini
     */
    public function perawatanItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PerawatanBerkalaSarprasItem::class, 'inventory_id');
    }

    /**
     * Riwayat pengurusan perbaikan atau penggantian (rusak / hilang) barang ini
     */
    public function perbaikanPenggantian(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PerbaikanPenggantianSarpras::class, 'inventory_id');
    }
}
