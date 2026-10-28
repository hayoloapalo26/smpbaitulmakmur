<?php

namespace App\Observers;

use App\Models\StudentCase;
use App\Models\BkReport;
use App\Models\CharacterScoreLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StudentCaseObserver
{
    public function created(StudentCase $studentCase): void
    {
        // 1. Hubungkan ke BkReport jika kasus direkomendasikan ke BK
        if ($studentCase->recommended_to_bk || $studentCase->ditangani_oleh === 'bk') {
            $this->syncBkReport($studentCase);
        }

        // 2. Hubungkan ke CharacterScoreLog (Pencatatan Poin Pelanggaran Karakter/Kedisiplinan)
        $this->recordDisciplinaryScoreLog($studentCase);
    }

    public function updated(StudentCase $studentCase): void
    {
        // 1. Hubungkan saat kasus dieskalasi ke BK
        if (
            ($studentCase->isDirty('recommended_to_bk') && $studentCase->recommended_to_bk)
            || ($studentCase->isDirty('ditangani_oleh') && $studentCase->ditangani_oleh === 'bk')
        ) {
            $this->syncBkReport($studentCase);
        }

        // 2. Hubungkan saat kasus diselesaikan: berikan catatan apresiasi pembinaan
        if ($studentCase->isDirty('status') && $studentCase->status === 'selesai') {
            $this->recordCaseResolutionScoreLog($studentCase);
        }
    }

    protected function syncBkReport(StudentCase $studentCase): void
    {
        $existing = BkReport::where('student_case_id', $studentCase->id)->first();

        $kategoriText = is_array($studentCase->kategori_kasus)
            ? implode(', ', $studentCase->kategori_kasus)
            : ($studentCase->kategori_kasus ?? 'Pelanggaran Disiplin');

        $tglKejadian = $studentCase->tanggal_kejadian
            ? $studentCase->tanggal_kejadian->translatedFormat('d F Y')
            : now()->translatedFormat('d F Y');

        $waliKelasName = $studentCase->waliKelas?->name ?? 'Wali Kelas';

        $content = "=== RUJUKAN KASUS SISWA DARI WALI KELAS ===\n"
            . "Nomor Kasus: " . ($studentCase->nomor_kasus ?? '-') . "\n"
            . "Tanggal Kejadian: {$tglKejadian}\n"
            . "Kategori Pelanggaran: {$kategoriText}\n"
            . "Kronologi Masalah:\n" . ($studentCase->deskripsi ?? '-') . "\n\n"
            . "Catatan Tindak Lanjut Wali Kelas ({$waliKelasName}):\n" . ($studentCase->catatan_wali_kelas ?? 'Belum ada catatan khusus.');

        $bkTeacherId = User::role('bk')->first()?->id ?? Auth::id() ?? 1;

        if (!$existing) {
            BkReport::create([
                'student_id'      => $studentCase->student_id,
                'student_case_id' => $studentCase->id,
                'tanggal'         => now(),
                'isi_laporan'     => $content,
                'bk_teacher_id'   => $bkTeacherId,
                'institution_id'  => $studentCase->institution_id ?? $studentCase->student?->institution_id,
            ]);
        } else {
            if (empty($existing->isi_laporan) || str_starts_with($existing->isi_laporan, '=== RUJUKAN KASUS')) {
                $existing->update([
                    'isi_laporan' => $content,
                ]);
            }
        }
    }

    protected function recordDisciplinaryScoreLog(StudentCase $studentCase): void
    {
        if (!$studentCase->student_id) return;

        $kategoriText = is_array($studentCase->kategori_kasus)
            ? implode(', ', $studentCase->kategori_kasus)
            : ($studentCase->kategori_kasus ?? 'Pelanggaran Disiplin');

        CharacterScoreLog::create([
            'student_id'   => $studentCase->student_id,
            'source'       => 'guru_kelas',
            'category'     => 'kedisiplinan',
            'description'  => "Pelanggaran Kasus ({$studentCase->nomor_kasus}): {$kategoriText} - " . \Illuminate\Support\Str::limit($studentCase->deskripsi ?? '', 60),
            'point_change' => -5,
            'tanggal'      => $studentCase->tanggal_kejadian ?? today(),
            'institution_id'=> $studentCase->institution_id ?? $studentCase->student?->institution_id,
        ]);
    }

    protected function recordCaseResolutionScoreLog(StudentCase $studentCase): void
    {
        if (!$studentCase->student_id) return;

        CharacterScoreLog::create([
            'student_id'   => $studentCase->student_id,
            'source'       => 'guru_kelas',
            'category'     => 'kedisiplinan',
            'description'  => "Penyelesaian Kasus ({$studentCase->nomor_kasus}): Pembinaan tuntas dan siswa berkomitmen memperbaiki sikap.",
            'point_change' => 2,
            'tanggal'      => today(),
            'institution_id'=> $studentCase->institution_id ?? $studentCase->student?->institution_id,
        ]);
    }
}
