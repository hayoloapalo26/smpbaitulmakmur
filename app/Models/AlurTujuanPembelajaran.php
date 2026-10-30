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

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function rppms()
    {
        return $this->hasMany(Rppm::class, 'alur_tujuan_pembelajaran_id');
    }

    /**
     * Otomatis membuat dan memperbarui ATP dari seluruh inputan TP oleh guru mapel bersangkutan
     * Mengakumulasikan seluruh TP dan mengurutkan berdasarkan kode CP dan kode TP secara sistematis (1.1, 1.2, ..., 2.1, dst.)
     */
    public static function syncForTeacherAndSubject(int $guruId, int $subjectId, ?string $tingkat = '7', ?string $tahunAjaran = '2026/2027', ?string $semester = 'Ganjil'): ?self
    {
        $guru = User::find($guruId);
        $subject = Subject::find($subjectId);

        if (!$guru || !$subject) {
            return null;
        }

        // Ambil semua TP yang diinput oleh guru ini untuk mapel ini
        $query = TujuanPembelajaran::where('guru_id', $guruId)
            ->where('subject_id', $subjectId);

        if ($tingkat) {
            $query->where(function ($q) use ($tingkat) {
                $q->where('kelas_tingkat', $tingkat)
                  ->orWhereNull('kelas_tingkat');
            });
        }

        // Urutkan TP secara natural berdasarkan kode_tp (1.1, 1.2, ..., 1.5, 2.1, dst.)
        $allTps = $query->with('capaianPembelajaran')->get()->sort(function ($a, $b) {
            return strnatcmp($a->kode_tp ?? '', $b->kode_tp ?? '');
        })->values();

        $tpIds = $allTps->pluck('id')->toArray();
        $count = count($tpIds);

        // Susun urutan alur terstruktur yang memuat kode CP dan kode TP
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

        $atp = static::firstOrCreate(
            [
                'guru_id' => $guruId,
                'subject_id' => $subjectId,
                'tingkat' => $tingkat ?? '7',
                'tahun_ajaran' => $tahunAjaran ?? '2026/2027',
            ],
            [
                'institution_id' => $guru->institution_id,
                'judul_atp' => "Alur Tujuan Pembelajaran (ATP) - {$subject->name} Kelas {$tingkat} ({$count} TP Terakumulasi)",
                'fase' => 'Fase D (SMP)',
                'semester' => $semester ?? 'Ganjil',
                'tp_ids' => $tpIds,
                'urutan_alur' => $urutanAlur,
                'total_alokasi_jp' => $totalJp,
            ]
        );

        $atp->update([
            'tp_ids' => $tpIds,
            'urutan_alur' => $urutanAlur,
            'judul_atp' => "Alur Tujuan Pembelajaran (ATP) - {$subject->name} Kelas {$tingkat} ({$count} TP Terakumulasi)",
            'total_alokasi_jp' => $totalJp,
        ]);

        return $atp;
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
}
