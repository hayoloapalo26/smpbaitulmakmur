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

        $allTps = $query->orderBy('id', 'asc')->get();
        $tpIds = $allTps->pluck('id')->toArray();
        $count = count($tpIds);

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
            ]
        );

        $atp->update([
            'tp_ids' => $tpIds,
            'judul_atp' => "Alur Tujuan Pembelajaran (ATP) - {$subject->name} Kelas {$tingkat} ({$count} TP Terakumulasi)",
            'total_alokasi_jp' => $allTps->sum('alokasi_jp') ?: ($count * 2),
        ]);

        return $atp;
    }

    /**
     * Mengambil koleksi TujuanPembelajaran yang diakumulasikan
     */
    public function getAccumulatedTpsAttribute()
    {
        if (empty($this->tp_ids)) {
            return collect();
        }

        return TujuanPembelajaran::whereIn('id', $this->tp_ids)
            ->get()
            ->sortBy(function ($tp) {
                return array_search($tp->id, $this->tp_ids);
            });
    }
}
