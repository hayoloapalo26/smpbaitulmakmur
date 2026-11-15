<?php

namespace App\Observers;

use App\Models\StudentCase;
use App\Models\BkReport;
use App\Models\CharacterScoreLog;
use App\Models\StudentViolationLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StudentCaseObserver
{
    public function created(StudentCase $studentCase): void
    {
        // 1. Hubungkan ke BkReport jika kasus direkomendasikan ke BK
        if ($studentCase->recommended_to_bk || $studentCase->ditangani_oleh === 'bk') {
            $this->syncBkReport($studentCase);
        }

        // 2. Hubungkan ke StudentViolationLog (Rekapitulasi Skor Poin Pelanggaran & CharacterScoreLog)
        $this->syncViolationLog($studentCase);
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

        // 2. Sinkronkan ke StudentViolationLog jika terdapat perubahan rincian kasus atau poin
        if (
            $studentCase->isDirty('poin_pelanggaran')
            || $studentCase->isDirty('kategori_kasus')
            || $studentCase->isDirty('deskripsi')
            || $studentCase->isDirty('tanggal_kejadian')
            || $studentCase->isDirty('catatan_wali_kelas')
        ) {
            $this->syncViolationLog($studentCase);
        }

        // 3. Hubungkan saat kasus diselesaikan: berikan catatan apresiasi pembinaan
        if ($studentCase->isDirty('status') && $studentCase->status === 'selesai') {
            $this->recordCaseResolutionScoreLog($studentCase);
        }
    }

    public function deleted(StudentCase $studentCase): void
    {
        StudentViolationLog::where('student_case_id', $studentCase->id)->delete();
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

    protected function syncViolationLog(StudentCase $studentCase): void
    {
        if (!$studentCase->student_id) return;

        $kategoriText = is_array($studentCase->kategori_kasus)
            ? implode(', ', $studentCase->kategori_kasus)
            : ($studentCase->kategori_kasus ?? 'Pelanggaran Disiplin');

        $poin = (int) ($studentCase->poin_pelanggaran ?? 5);
        if ($poin <= 0) {
            $poin = 5;
        }

        $student = $studentCase->student ?: Student::withoutGlobalScopes()->find($studentCase->student_id);

        $existing = StudentViolationLog::where('student_case_id', $studentCase->id)->first();

        $payload = [
            'student_case_id'        => $studentCase->id,
            'tanggal'                => $studentCase->tanggal_kejadian ?? ($studentCase->created_at ? $studentCase->created_at->toDateString() : today()),
            'jam'                    => $studentCase->created_at ? $studentCase->created_at->format('H:i') : now()->format('H:i'),
            'student_id'             => $studentCase->student_id,
            'classroom_id'           => $student?->classroom_id,
            'bentuk_pelanggaran'     => "Kasus ({$studentCase->nomor_kasus}): {$kategoriText}" . ($studentCase->deskripsi ? " - " . Str::limit($studentCase->deskripsi, 80) : ''),
            'poin'                   => $poin,
            'teacher_id'             => $studentCase->wali_kelas_id ?? Auth::id() ?? 1,
            'tindak_lanjut_langsung' => $studentCase->catatan_wali_kelas ?: ($studentCase->deskripsi ?: 'Dicatat dalam rekam jejak kasus pelanggaran siswa'),
            'acc_piket'              => true,
            'acc_piket_by'           => Auth::id(),
            'acc_piket_at'           => now(),
            'institution_id'         => $studentCase->institution_id ?? $student?->institution_id,
        ];

        if ($existing) {
            $existing->update($payload);
        } else {
            StudentViolationLog::create($payload);
        }
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
