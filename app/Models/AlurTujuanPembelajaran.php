<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlurTujuanPembelajaran extends Model
{
    use \App\Models\Traits\BelongsToInstitution;
    use HasFactory;

    protected $table = 'alur_tujuan_pembelajarans';

    protected $guarded = ['id'];

    protected $casts = [
        'tp_ids'      => 'array',
        'urutan_alur' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($atp) {
            if (empty($atp->kode_atp)) {
                if ($atp->capaian_pembelajaran_id) {
                    $cp = CapaianPembelajaran::find($atp->capaian_pembelajaran_id);
                    $atp->kode_atp = $cp?->kode_cp ?: '1';
                } else {
                    $count = static::where('guru_id', $atp->guru_id)
                        ->where('subject_id', $atp->subject_id)
                        ->where('tingkat', $atp->tingkat)
                        ->count() + 1;
                    $atp->kode_atp = (string) $count;
                }
            }
        });
    }

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function capaianPembelajaran()
    {
        return $this->belongsTo(CapaianPembelajaran::class, 'capaian_pembelajaran_id');
    }

    public function rppms()
    {
        return $this->hasMany(Rppm::class, 'alur_tujuan_pembelajaran_id');
    }

    /**
     * Otomatis membuat dan memperbarui ATP dari seluruh inputan TP oleh guru mapel bersangkutan
     * Mengakumulasikan TP sesuai kode CP (misal CP 1 -> ATP 1 dengan TP 1.1 - 1.5, CP 2 -> ATP 2 dengan TP 2.1 - 2.2)
     */
    public static function syncForTeacherAndSubject(
        int $guruId,
        int $subjectId,
        ?string $tingkat = '7',
        ?string $tahunAjaran = '2026/2027',
        ?string $semester = 'Ganjil',
        ?int $targetCpId = null
    ): ?self {
        $guru = User::find($guruId);
        $subject = Subject::find($subjectId);

        if (!$guru || !$subject) {
            return null;
        }

        // Jika targetCpId ditentukan, sinkronkan ATP spesifik untuk CP tersebut
        if ($targetCpId) {
            $cp = CapaianPembelajaran::find($targetCpId);
            if (!$cp) {
                return null;
            }

            $cpKode = $cp->kode_cp ?: '1';
            $kodeAtp = $cpKode;

            // Ambil semua TP di bawah CP ini
            $cpTps = TujuanPembelajaran::where('capaian_pembelajaran_id', $cp->id)
                ->get()
                ->sort(function ($a, $b) {
                    return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
                })
                ->values();

            $tpIds = $cpTps->pluck('id')->toArray();
            $count = count($tpIds);
            $tpSummary = $count > 0 ? " (" . $cpTps->pluck('kode_tp')->implode(', ') . ")" : "";

            // Susun urutan alur terstruktur yang memuat kode CP dan kode TP
            $urutanAlur = $cpTps->map(function ($tp, $index) use ($cpKode, $cp) {
                $tahapNum = $index + 1;
                return [
                    'tahap' => "Tahap {$tahapNum} [TP {$tp->kode_tp}]",
                    'kode_tp' => $tp->kode_tp,
                    'kode_cp' => $cpKode,
                    'elemen' => $cp->elemen ?? '',
                    'materi' => $tp->materi ?: ($cp->elemen ?: "Materi TP {$tp->kode_tp}"),
                    'alokasi_jp' => 2,
                    'catatan_alur' => $tp->deskripsi_tp,
                ];
            })->toArray();

            $totalJp = $count * 2;

            $atp = static::updateOrCreate(
                [
                    'guru_id' => $guruId,
                    'subject_id' => $subjectId,
                    'tingkat' => $tingkat ?? $cp->tingkat ?? '7',
                    'capaian_pembelajaran_id' => $cp->id,
                ],
                [
                    'institution_id' => $guru->institution_id,
                    'kode_atp' => (string) $kodeAtp,
                    'judul_atp' => "Alur Tujuan Pembelajaran (ATP {$kodeAtp}) - {$subject->name} Kelas {$tingkat} [CP {$cpKode}: {$cp->elemen}] ({$count} TP{$tpSummary})",
                    'fase' => $cp->fase ?? 'Fase D (SMP)',
                    'tahun_ajaran' => $tahunAjaran ?? $cp->tahun_ajaran ?? '2026/2027',
                    'semester' => $semester ?? 'Ganjil',
                    'tp_ids' => $tpIds,
                    'urutan_alur' => $urutanAlur,
                    'total_alokasi_jp' => $totalJp,
                ]
            );

            return $atp;
        }

        // Jika tidak ada targetCpId spesifik, sinkronkan untuk setiap CP yang dimiliki guru pada mapel & tingkat ini
        $allCps = CapaianPembelajaran::where('guru_id', $guruId)
            ->where('subject_id', $subjectId);

        if ($tingkat) {
            $allCps->where('tingkat', $tingkat);
        }

        $allCps = $allCps->orderBy('id', 'asc')->get();

        $primaryAtp = null;
        foreach ($allCps as $cpItem) {
            $syncedAtp = static::syncForTeacherAndSubject(
                $guruId,
                $subjectId,
                $tingkat,
                $tahunAjaran,
                $semester,
                $cpItem->id
            );
            if (!$primaryAtp) {
                $primaryAtp = $syncedAtp;
            }
        }

        // Jika guru belum memiliki CP, fallback ke akumulasi TP umum
        if (!$primaryAtp) {
            $query = TujuanPembelajaran::where('guru_id', $guruId)
                ->where('subject_id', $subjectId);

            if ($tingkat) {
                $query->where(function ($q) use ($tingkat) {
                    $q->where('kelas_tingkat', $tingkat)
                      ->orWhereNull('kelas_tingkat');
                });
            }

            $allTps = $query->with('capaianPembelajaran')->get()->sort(function ($a, $b) {
                return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
            })->values();

            $tpIds = $allTps->pluck('id')->toArray();
            $count = count($tpIds);

            $urutanAlur = $allTps->map(function ($tp, $index) {
                $cp = $tp->capaianPembelajaran;
                $cpKode = $cp?->kode_cp ?: '1';
                $tahapNum = $index + 1;

                return [
                    'tahap' => "Tahap {$tahapNum} [TP {$tp->kode_tp}]",
                    'kode_tp' => $tp->kode_tp,
                    'kode_cp' => $cpKode,
                    'elemen' => $cp?->elemen ?? '',
                    'materi' => $tp->materi ?: ($cp?->elemen ?: "Materi TP {$tp->kode_tp}"),
                    'alokasi_jp' => 2,
                    'catatan_alur' => $tp->deskripsi_tp,
                ];
            })->toArray();

            $totalJp = $count * 2;

            $primaryAtp = static::updateOrCreate(
                [
                    'guru_id' => $guruId,
                    'subject_id' => $subjectId,
                    'tingkat' => $tingkat ?? '7',
                    'capaian_pembelajaran_id' => null,
                ],
                [
                    'institution_id' => $guru->institution_id,
                    'kode_atp' => '1',
                    'judul_atp' => "Alur Tujuan Pembelajaran (ATP 1) - {$subject->name} Kelas {$tingkat} ({$count} TP Terakumulasi)",
                    'fase' => 'Fase D (SMP)',
                    'tahun_ajaran' => $tahunAjaran ?? '2026/2027',
                    'semester' => $semester ?? 'Ganjil',
                    'tp_ids' => $tpIds,
                    'urutan_alur' => $urutanAlur,
                    'total_alokasi_jp' => $totalJp,
                ]
            );
        }

        return $primaryAtp;
    }

    /**
     * Mengambil koleksi TujuanPembelajaran yang diakumulasikan dan terurut natural berdasarkan kode_tp
     */
    public function getAccumulatedTpsAttribute()
    {
        if (empty($this->tp_ids)) {
            return collect();
        }

        return TujuanPembelajaran::with('capaianPembelajaran')
            ->whereIn('id', $this->tp_ids)
            ->get()
            ->sort(function ($a, $b) {
                return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
            })
            ->values();
    }

    /**
     * Ringkasan daftar kode TP yang terakumulasi
     */
    public function getTpCodesSummaryAttribute(): string
    {
        $tps = $this->accumulated_tps;
        if ($tps->isEmpty()) {
            return 'Belum ada TP';
        }

        return $tps->pluck('kode_tp')->filter()->implode(', ');
    }

    /**
     * Label dropdown lengkap dengan kode ATP dan kode CP yang bersangkutan
     */
    public function getDisplayOptionLabelAttribute(): string
    {
        $atpKode = $this->kode_atp ? "ATP {$this->kode_atp}" : "ATP";
        $cp = $this->capaianPembelajaran;
        $cpKode = $cp ? ($cp->kode_cp ? "CP {$cp->kode_cp}" : "CP") : "Semua CP";
        $tpSummary = $this->tp_codes_summary !== 'Belum ada TP' ? " [{$this->tp_codes_summary}]" : "";

        return "[{$atpKode}] [{$cpKode}] {$this->judul_atp}{$tpSummary}";
    }
}
