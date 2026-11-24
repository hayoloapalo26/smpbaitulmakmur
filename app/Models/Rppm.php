<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rppm extends Model
{
    use \App\Models\Traits\BelongsToInstitution;
    use HasFactory;

    protected $table = 'rppms';

    protected $fillable = [
        'institution_id',
        'guru_id',
        'kelas_fase',
        'fase',
        'fases',
        'mata_pelajaran',
        'capaian_pembelajaran_id',
        'alur_tujuan_pembelajaran_id',
        'selected_tp_ids',
        'status',
        'catatan_kepala_sekolah',
        'reviewed_by',
        'reviewed_at',
        'topik',
        'alokasi_waktu',
        'link_drive',
        'content',
    ];

    protected $casts = [
        'content'         => 'array',
        'selected_tp_ids' => 'array',
        'fases'           => 'array',
        'reviewed_at'     => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($rppm) {
            if (!empty($rppm->fases) && is_array($rppm->fases)) {
                $rppm->fases = array_values(array_unique(array_filter($rppm->fases)));
                $faseNames = array_map(fn ($f) => "Fase {$f}", $rppm->fases);
                $rppm->fase = implode(', ', $faseNames);
            } elseif (empty($rppm->fases) && !empty($rppm->fase)) {
                $rppm->fases = ['D'];
            }

            if (empty($rppm->kelas_fase)) {
                $faseLabel = $rppm->fase ?: 'Fase D';
                $rppm->kelas_fase = "Kelas 7 / {$faseLabel}";
            }
        });
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function capaianPembelajaran()
    {
        return $this->belongsTo(CapaianPembelajaran::class, 'capaian_pembelajaran_id');
    }

    public function alurTujuanPembelajaran()
    {
        return $this->belongsTo(AlurTujuanPembelajaran::class, 'alur_tujuan_pembelajaran_id');
    }

    public function getSelectedTpsAttribute()
    {
        if (empty($this->selected_tp_ids)) {
            return collect();
        }

        return TujuanPembelajaran::whereIn('id', $this->selected_tp_ids)->get();
    }

    public function getLinkDriveAttribute($value): ?string
    {
        if (!empty($value)) {
            return $value;
        }

        if (is_array($this->content) && !empty($this->content['link_drive'])) {
            return $this->content['link_drive'];
        }

        return null;
    }

    /**
     * Mengambil seluruh ID Tujuan Pembelajaran (TP) yang tercatat di dokumen RPPM ini,
     * baik format baru (selected_tp_ids), format transisi (content.selected_tp_dropdown),
     * maupun format lama tersimpan (content.tujuan_pembelajaran).
     */
    public function getAllTpIds(): array
    {
        $ids = [];

        // 1. Dari kolom selected_tp_ids (format baru)
        if (!empty($this->selected_tp_ids) && is_array($this->selected_tp_ids)) {
            $ids = array_merge($ids, $this->selected_tp_ids);
        }

        // 2. Dari content.selected_tp_dropdown (format transisi)
        if (is_array($this->content) && !empty($this->content['selected_tp_dropdown']) && is_array($this->content['selected_tp_dropdown'])) {
            $ids = array_merge($ids, $this->content['selected_tp_dropdown']);
        }

        // 3. Dari content.tujuan_pembelajaran (format lama tersimpan)
        if (empty($ids) && is_array($this->content) && !empty($this->content['tujuan_pembelajaran']) && is_array($this->content['tujuan_pembelajaran'])) {
            foreach ($this->content['tujuan_pembelajaran'] as $tpItem) {
                $rumusan = is_array($tpItem) ? ($tpItem['rumusan'] ?? '') : (string) $tpItem;
                if ($rumusan) {
                    if (preg_match('/\[(?:TP\s*)?([0-9\.]+)\]/i', $rumusan, $m)) {
                        $foundId = TujuanPembelajaran::where('kode_tp', $m[1])
                            ->when($this->mata_pelajaran, fn ($q) => $q->whereHas('subject', fn ($sq) => $sq->where('name', $this->mata_pelajaran)))
                            ->value('id');
                        if ($foundId) $ids[] = (int) $foundId;
                    } else {
                        $clean = trim(preg_replace('/^\[.*?\]\s*/', '', $rumusan));
                        if ($clean) {
                            $found = TujuanPembelajaran::where('deskripsi_tp', 'like', "%{$clean}%")->first();
                            if ($found) $ids[] = (int) $found->id;
                        }
                    }
                }
            }
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    /**
     * Mengambil ID Capaian Pembelajaran (CP) rujukan dari dokumen RPPM ini.
     */
    public function getEffectiveCpId(): ?int
    {
        if ($this->capaian_pembelajaran_id) {
            return (int) $this->capaian_pembelajaran_id;
        }

        if (is_array($this->content) && !empty($this->content['capaian_pembelajaran_id'])) {
            return (int) $this->content['capaian_pembelajaran_id'];
        }

        return null;
    }

    /**
     * Mengambil Topik / Materi pokok dari dokumen RPPM ini.
     */
    public function getEffectiveMateri(): ?string
    {
        if (!empty($this->topik)) {
            return $this->topik;
        }

        if (is_array($this->content) && !empty($this->content['topik_pembelajaran'])) {
            return $this->content['topik_pembelajaran'];
        }

        return $this->capaianPembelajaran?->elemen;
    }
}
