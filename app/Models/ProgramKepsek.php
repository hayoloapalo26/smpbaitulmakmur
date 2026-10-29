<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramKepsek extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    use HasFactory;

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function source()
    {
        if ($this->source_type && $this->source_id && class_exists($this->source_type)) {
            return $this->belongsTo($this->source_type, 'source_id');
        }
        return null;
    }

    /**
     * Menarik seluruh program kerja dari semua bidang (Kurikulum, Kesiswaan, Sarpras, Humas, BK, Wali Kelas, OSIS)
     * dan juga program kerja yang dibuat oleh Yayasan yang ditujukan ke lembaga ini.
     */
    public static function syncFromAllDivisionsAndYayasan(?int $institutionId = null, ?int $userId = null): array
    {
        $userId = $userId ?? auth()->id() ?? User::role('kepala_sekolah')->first()?->id ?? User::first()?->id;
        $institutionId = $institutionId ?? auth()->user()?->institution_id;

        $imported = 0;
        $details = [
            'yayasan' => 0,
            'kurikulum' => 0,
            'kesiswaan' => 0,
            'sarpras' => 0,
            'humas' => 0,
            'bk' => 0,
            'wali_kelas' => 0,
            'osis' => 0,
        ];

        // 1. Tarik Program Kerja Yayasan yang ditujukan ke lembaga ini
        $yayasanPrograms = YayasanProgram::query();
        if ($institutionId) {
            $yayasanPrograms->whereHas('institutions', fn ($q) => $q->where('institutions.id', $institutionId));
        }
        foreach ($yayasanPrograms->get() as $yp) {
            $exists = static::where(function ($q) use ($yp) {
                $q->where('source_type', YayasanProgram::class)
                  ->where('source_id', $yp->id);
            })->orWhere(function ($q) use ($yp) {
                $q->where('nama_program', $yp->name)
                  ->where('kategori', 'Program Yayasan');
            })->exists();

            if (!$exists) {
                $isCompleted = false;
                if ($institutionId) {
                    $pivot = \Illuminate\Support\Facades\DB::table('institution_yayasan_program')
                        ->where('institution_id', $institutionId)
                        ->where('yayasan_program_id', $yp->id)
                        ->first();
                    $isCompleted = (bool) ($pivot?->is_completed ?? false);
                }

                static::create([
                    'user_id' => $userId,
                    'institution_id' => $institutionId,
                    'tahun_ajaran' => '2026/2027',
                    'nama_program' => $yp->name,
                    'kategori' => 'Program Yayasan',
                    'tipe_pelaksanaan' => 'Tahunan',
                    'sifat' => 'Wajib',
                    'tujuan' => $yp->description ?? 'Instruksi program kerja Yayasan yang ditujukan ke lembaga.',
                    'teknis_pelaksanaan' => 'Program terintegrasi dari Yayasan Baitul Makmur untuk lembaga.',
                    'sasaran' => 'Seluruh Lembaga Sekolah',
                    'waktu_pelaksanaan' => 'Tahun Ajaran 2026/2027',
                    'status_pelaksanaan' => $isCompleted ? 'Selesai' : 'Sedang Berjalan',
                    'source_type' => YayasanProgram::class,
                    'source_id' => $yp->id,
                ]);
                $imported++;
                $details['yayasan']++;
            }
        }

        // 2. Tarik Program Kurikulum
        $kurikulumPrograms = ProgramKurikulum::query();
        if ($institutionId) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('program_kurikulums', 'institution_id')) {
                $kurikulumPrograms->where('institution_id', $institutionId);
            } else {
                $kurikulumPrograms->whereHas('user', fn ($u) => $u->where('institution_id', $institutionId));
            }
        }
        foreach ($kurikulumPrograms->get() as $p) {
            $exists = static::where(function ($q) use ($p) {
                $q->where('source_type', ProgramKurikulum::class)->where('source_id', $p->id);
            })->orWhere(function ($q) use ($p) {
                $q->where('nama_program', $p->nama_program)->where('kategori', 'Kurikulum');
            })->exists();

            if (!$exists) {
                $status = in_array($p->status_pelaksanaan, ['Rencana', 'Sedang Berjalan', 'Selesai', 'Batal'])
                    ? $p->status_pelaksanaan : 'Sedang Berjalan';

                static::create([
                    'user_id' => $userId,
                    'institution_id' => $institutionId,
                    'tahun_ajaran' => $p->tahun_ajaran ?? '2026/2027',
                    'nama_program' => $p->nama_program,
                    'kategori' => 'Kurikulum',
                    'tipe_pelaksanaan' => $p->tipe_pelaksanaan ?? 'Semesteran',
                    'sifat' => $p->sifat ?? 'Terjadwal',
                    'tujuan' => $p->tujuan ?? 'Program kerja bidang akademik dan kurikulum pembelajaran.',
                    'teknis_pelaksanaan' => $p->teknis_pelaksanaan ?? null,
                    'sasaran' => $p->target ?? 'Siswa & Guru Mapel',
                    'waktu_pelaksanaan' => $p->waktu_pelaksanaan ?? null,
                    'estimasi_anggaran' => $p->anggaran ?? null,
                    'status_pelaksanaan' => $status,
                    'source_type' => ProgramKurikulum::class,
                    'source_id' => $p->id,
                ]);
                $imported++;
                $details['kurikulum']++;
            }
        }

        // 3. Tarik Program Kesiswaan
        $kesiswaanPrograms = ProgramKesiswaan::query();
        if ($institutionId) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('program_kesiswaans', 'institution_id')) {
                $kesiswaanPrograms->where('institution_id', $institutionId);
            } else {
                $kesiswaanPrograms->whereHas('user', fn ($u) => $u->where('institution_id', $institutionId));
            }
        }
        foreach ($kesiswaanPrograms->get() as $p) {
            $exists = static::where(function ($q) use ($p) {
                $q->where('source_type', ProgramKesiswaan::class)->where('source_id', $p->id);
            })->orWhere(function ($q) use ($p) {
                $q->where('nama_program', $p->nama_program)->where('kategori', 'Kesiswaan');
            })->exists();

            if (!$exists) {
                $status = in_array($p->status_pelaksanaan, ['Rencana', 'Sedang Berjalan', 'Selesai', 'Batal'])
                    ? $p->status_pelaksanaan : 'Sedang Berjalan';

                static::create([
                    'user_id' => $userId,
                    'institution_id' => $institutionId,
                    'tahun_ajaran' => $p->tahun_ajaran ?? '2026/2027',
                    'nama_program' => $p->nama_program,
                    'kategori' => 'Kesiswaan',
                    'tipe_pelaksanaan' => $p->tipe_pelaksanaan ?? 'Semesteran',
                    'sifat' => $p->sifat ?? 'Terjadwal',
                    'tujuan' => $p->tujuan ?? 'Program pembinaan karakter dan kesiswaan.',
                    'teknis_pelaksanaan' => $p->teknis_pelaksanaan ?? null,
                    'sasaran' => $p->sasaran ?? 'Seluruh Siswa',
                    'waktu_pelaksanaan' => $p->waktu_pelaksanaan ?? null,
                    'estimasi_anggaran' => $p->anggaran ?? null,
                    'status_pelaksanaan' => $status,
                    'source_type' => ProgramKesiswaan::class,
                    'source_id' => $p->id,
                ]);
                $imported++;
                $details['kesiswaan']++;
            }
        }

        // 4. Tarik Program Sarpras
        $sarprasPrograms = ProgramSarpras::query();
        if ($institutionId) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('program_sarpras', 'institution_id')) {
                $sarprasPrograms->where('institution_id', $institutionId);
            } else {
                $sarprasPrograms->whereHas('user', fn ($u) => $u->where('institution_id', $institutionId));
            }
        }
        foreach ($sarprasPrograms->get() as $p) {
            $exists = static::where(function ($q) use ($p) {
                $q->where('source_type', ProgramSarpras::class)->where('source_id', $p->id);
            })->orWhere(function ($q) use ($p) {
                $q->where('nama_program', $p->nama_program)->where('kategori', 'Sarpras');
            })->exists();

            if (!$exists) {
                $status = in_array($p->status_pelaksanaan, ['Rencana', 'Sedang Berjalan', 'Selesai', 'Batal'])
                    ? $p->status_pelaksanaan : 'Sedang Berjalan';

                static::create([
                    'user_id' => $userId,
                    'institution_id' => $institutionId,
                    'tahun_ajaran' => $p->tahun_ajaran ?? '2026/2027',
                    'nama_program' => $p->nama_program,
                    'kategori' => 'Sarpras',
                    'tipe_pelaksanaan' => $p->tipe_pelaksanaan ?? 'Tahunan',
                    'sifat' => $p->sifat ?? 'Terjadwal',
                    'tujuan' => $p->tujuan ?? 'Program pengadaan dan pemeliharaan fasilitas sarana prasarana.',
                    'teknis_pelaksanaan' => $p->teknis_pelaksanaan ?? null,
                    'sasaran' => $p->sasaran ?? 'Fasilitas & Sarana Sekolah',
                    'waktu_pelaksanaan' => $p->waktu_pelaksanaan ?? null,
                    'estimasi_anggaran' => $p->anggaran ?? null,
                    'status_pelaksanaan' => $status,
                    'source_type' => ProgramSarpras::class,
                    'source_id' => $p->id,
                ]);
                $imported++;
                $details['sarpras']++;
            }
        }

        // 5. Tarik Program Humas
        $humasPrograms = ProgramHumas::query();
        if ($institutionId) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('program_humas', 'institution_id')) {
                $humasPrograms->where('institution_id', $institutionId);
            } else {
                $humasPrograms->whereHas('user', fn ($u) => $u->where('institution_id', $institutionId));
            }
        }
        foreach ($humasPrograms->get() as $p) {
            $exists = static::where(function ($q) use ($p) {
                $q->where('source_type', ProgramHumas::class)->where('source_id', $p->id);
            })->orWhere(function ($q) use ($p) {
                $q->where('nama_program', $p->nama_program)->where('kategori', 'Humas');
            })->exists();

            if (!$exists) {
                $status = in_array($p->status_pelaksanaan, ['Rencana', 'Sedang Berjalan', 'Selesai', 'Batal'])
                    ? $p->status_pelaksanaan : 'Sedang Berjalan';

                static::create([
                    'user_id' => $userId,
                    'institution_id' => $institutionId,
                    'tahun_ajaran' => $p->tahun_ajaran ?? '2026/2027',
                    'nama_program' => $p->nama_program,
                    'kategori' => 'Humas',
                    'tipe_pelaksanaan' => $p->tipe_pelaksanaan ?? 'Semesteran',
                    'sifat' => $p->sifat ?? 'Terjadwal',
                    'tujuan' => $p->tujuan ?? 'Program hubungan masyarakat dan kemitraan lembaga.',
                    'teknis_pelaksanaan' => $p->teknis_pelaksanaan ?? null,
                    'sasaran' => $p->sasaran ?? 'Wali Murid & Masyarakat',
                    'waktu_pelaksanaan' => $p->waktu_pelaksanaan ?? null,
                    'estimasi_anggaran' => $p->anggaran ?? null,
                    'status_pelaksanaan' => $status,
                    'source_type' => ProgramHumas::class,
                    'source_id' => $p->id,
                ]);
                $imported++;
                $details['humas']++;
            }
        }

        // 6. Tarik Program Bimbingan Konseling (BK)
        $bkPrograms = BkWorkProgram::query();
        if ($institutionId) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('bk_work_programs', 'institution_id')) {
                $bkPrograms->where('institution_id', $institutionId);
            } else {
                $bkPrograms->whereHas('user', fn ($u) => $u->where('institution_id', $institutionId));
            }
        }
        foreach ($bkPrograms->get() as $p) {
            $name = $p->judul_program ?? $p->nama_program ?? null;
            if (!$name) continue;

            $exists = static::where(function ($q) use ($p) {
                $q->where('source_type', BkWorkProgram::class)->where('source_id', $p->id);
            })->orWhere(function ($q) use ($name) {
                $q->where('nama_program', $name)->where('kategori', 'Bimbingan Konseling');
            })->exists();

            if (!$exists) {
                $status = in_array(strtolower($p->status ?? ''), ['selesai', 'terlaksana']) ? 'Selesai' : 'Sedang Berjalan';

                static::create([
                    'user_id' => $userId,
                    'institution_id' => $institutionId,
                    'tahun_ajaran' => $p->tahun_ajaran ?? '2026/2027',
                    'nama_program' => $name,
                    'kategori' => 'Bimbingan Konseling',
                    'tipe_pelaksanaan' => $p->tipe_pelaksanaan ?? 'Bulanan',
                    'sifat' => $p->sifat ?? 'Kontinu',
                    'tujuan' => $p->tujuan ?? 'Layanan bimbingan dan konseling siswa.',
                    'teknis_pelaksanaan' => $p->teknis_pelaksanaan ?? $p->deskripsi ?? null,
                    'sasaran' => $p->sasaran ?? 'Siswa Perlu Pembinaan',
                    'waktu_pelaksanaan' => $p->waktu_pelaksanaan ?? $p->tanggal ?? null,
                    'estimasi_anggaran' => $p->estimasi_anggaran ?? null,
                    'status_pelaksanaan' => $status,
                    'source_type' => BkWorkProgram::class,
                    'source_id' => $p->id,
                ]);
                $imported++;
                $details['bk']++;
            }
        }

        // 7. Tarik Program Wali Kelas
        $classPrograms = ClassProgram::with('classroom');
        if ($institutionId) {
            $classPrograms->whereHas('classroom', fn ($c) => $c->where('institution_id', $institutionId));
        }
        foreach ($classPrograms->get() as $p) {
            $programName = $p->nama_program . ($p->classroom ? " (Kelas {$p->classroom->name})" : '');
            $exists = static::where(function ($q) use ($p) {
                $q->where('source_type', ClassProgram::class)->where('source_id', $p->id);
            })->orWhere(function ($q) use ($programName) {
                $q->where('nama_program', $programName)->where('kategori', 'Wali Kelas');
            })->exists();

            if (!$exists) {
                $status = ($p->status === 'Selesai') ? 'Selesai' : 'Sedang Berjalan';

                static::create([
                    'user_id' => $userId,
                    'institution_id' => $institutionId,
                    'tahun_ajaran' => '2026/2027',
                    'nama_program' => $programName,
                    'kategori' => 'Wali Kelas',
                    'tipe_pelaksanaan' => 'Bulanan',
                    'sifat' => 'Terjadwal',
                    'tujuan' => $p->tujuan ?? 'Program pengelolaan dan kekompakan kelas.',
                    'teknis_pelaksanaan' => $p->deskripsi ?? null,
                    'sasaran' => $p->target ?? ($p->classroom ? "Siswa {$p->classroom->name}" : 'Siswa Kelas'),
                    'waktu_pelaksanaan' => $p->waktu_pelaksanaan ?? null,
                    'status_pelaksanaan' => $status,
                    'source_type' => ClassProgram::class,
                    'source_id' => $p->id,
                ]);
                $imported++;
                $details['wali_kelas']++;
            }
        }

        // 8. Tarik Program OSIS
        $osisPrograms = OsisProgram::query();
        if ($institutionId && \Illuminate\Support\Facades\Schema::hasColumn('osis_programs', 'institution_id')) {
            $osisPrograms->where('institution_id', $institutionId);
        }
        foreach ($osisPrograms->get() as $p) {
            $name = $p->name ?? $p->nama_program ?? null;
            if (!$name) continue;

            $exists = static::where(function ($q) use ($p) {
                $q->where('source_type', OsisProgram::class)->where('source_id', $p->id);
            })->orWhere(function ($q) use ($name) {
                $q->where('nama_program', $name)->where('kategori', 'OSIS');
            })->exists();

            if (!$exists) {
                static::create([
                    'user_id' => $userId,
                    'institution_id' => $institutionId,
                    'tahun_ajaran' => '2026/2027',
                    'nama_program' => $name,
                    'kategori' => 'OSIS',
                    'tipe_pelaksanaan' => 'Semesteran',
                    'sifat' => 'Terjadwal',
                    'tujuan' => $p->description ?? 'Program kerja pengembangan potensi siswa melalui OSIS.',
                    'sasaran' => 'Pengurus OSIS & Seluruh Siswa',
                    'status_pelaksanaan' => ($p->status === 'Selesai' || $p->is_completed) ? 'Selesai' : 'Sedang Berjalan',
                    'source_type' => OsisProgram::class,
                    'source_id' => $p->id,
                ]);
                $imported++;
                $details['osis']++;
            }
        }

        return [
            'total' => $imported,
            'details' => $details,
        ];
    }
}
