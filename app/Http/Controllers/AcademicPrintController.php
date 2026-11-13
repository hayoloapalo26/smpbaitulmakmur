<?php

namespace App\Http\Controllers;

use App\Models\AlurTujuanPembelajaran;
use App\Models\CapaianPembelajaran;
use App\Models\Classroom;
use App\Models\MeetingMinute;
use App\Models\Rppm;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentCase;
use App\Models\StudentViolationApproval;
use App\Models\TeacherCertificate;
use App\Models\TeacherLeaveRequest;
use App\Models\TeachingJournal;
use App\Models\TujuanPembelajaran;
use App\Models\User;
use App\Services\StudentViolationReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AcademicPrintController extends Controller
{
    /**
     * Konfigurasi PDF standar untuk kualitas cetak terbaik.
     */
    protected function generatePdf($view, array $data, string $paper = 'a4', string $orientation = 'portrait', string $filename = 'dokumen.pdf')
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper($paper, $orientation)
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
                'dpi' => 120,
            ]);

        if (request()->query('download') == '1') {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename, ['Attachment' => false]);
    }

    /**
     * 1. Cetak Dokumen RPPM & Perangkat Pembelajaran (Modul Ajar)
     */
    public function rppmPdf(Rppm $rppm)
    {
        $user = auth()->user();
        if ($user && !$user->hasAnyRole([
            'superadmin',
            'kepala_sekolah',
            'waka_kurikulum',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
        ])) {
            if ((int) $rppm->guru_id !== (int) $user->id) {
                abort(403, 'Akses Ditolak: Anda hanya dapat melihat dokumen RPPM milik Anda sendiri.');
            }
        }

        $rppm->load(['guru', 'capaianPembelajaran', 'alurTujuanPembelajaran']);
        $selectedTps = $rppm->selected_tps;

        $titleSlug = Str::slug($rppm->topik ?? 'rppm-modul-ajar');
        $guruSlug = Str::slug($rppm->guru?->name ?? 'guru');
        $filename = "rppm-modul-ajar-{$titleSlug}-{$guruSlug}.pdf";

        return $this->generatePdf('pdf.rppm_document', compact('rppm', 'selectedTps'), 'a4', 'portrait', $filename);
    }

    /**
     * 2. Cetak Lembar Jurnal Mengajar Harian (Per Sesi Pertemuan)
     */
    public function journalPdf(TeachingJournal $journal)
    {
        $user = auth()->user();
        if ($user && !$user->hasAnyRole([
            'superadmin',
            'kepala_sekolah',
            'waka_kurikulum',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
            'pengembang_penjamin_mutu',
        ])) {
            if ((int) $journal->teacher_id !== (int) $user->id) {
                abort(403, 'Akses Ditolak: Anda hanya dapat melihat jurnal mengajar milik Anda sendiri.');
            }
        }

        $journal->load(['teacher', 'classroom', 'subject', 'schedule', 'rppm', 'subjectAttendances.student']);
        $selectedTps = $journal->selected_tps;

        $teacherName = Str::slug($journal->teacher?->name ?? 'guru');
        $date = Carbon::parse($journal->tanggal)->format('Y-m-d');
        $filename = "jurnal-mengajar-{$teacherName}-{$date}-jam-{$journal->jam_ke}.pdf";

        return $this->generatePdf('pdf.teaching_journal_single', compact('journal', 'selectedTps'), 'a4', 'portrait', $filename);
    }

    /**
     * 3. Cetak Rekapitulasi Jurnal Mengajar Guru (Landscape)
     */
    public function journalRekapPdf(Request $request)
    {
        $query = TeachingJournal::with(['teacher', 'classroom', 'subject', 'rppm'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_ke', 'asc');

        $user = auth()->user();
        if (!$user->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            $query->where('teacher_id', $user->id);
        } elseif ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->query('teacher_id'));
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->query('classroom_id'));
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->query('start_date'), $request->query('end_date')]);
        } elseif ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereMonth('tanggal', $request->query('bulan'))
                  ->whereYear('tanggal', $request->query('tahun'));
        }

        $journals = $query->limit(200)->get();
        $filename = "rekap-jurnal-mengajar-" . date('Y-m-d') . ".pdf";

        return $this->generatePdf('pdf.teaching_journal_rekap', compact('journals'), 'a4', 'landscape', $filename);
    }

    /**
     * 4. Cetak Dokumen Alur Tujuan Pembelajaran (ATP)
     */
    public function atpPdf(AlurTujuanPembelajaran $atp)
    {
        $atp->load(['guru', 'subject', 'capaianPembelajaran']);
        $accumulatedTps = $atp->accumulated_tps;

        $subjectName = Str::slug($atp->subject?->name ?? 'mapel');
        $atpKode = $atp->kode_atp ?? '1';
        $filename = "dokumen-atp-{$subjectName}-atp-{$atpKode}.pdf";

        return $this->generatePdf('pdf.atp_document', compact('atp', 'accumulatedTps'), 'a4', 'landscape', $filename);
    }

    /**
     * 5. Cetak Dokumen Capaian Pembelajaran (CP)
     */
    public function cpPdf(CapaianPembelajaran $cp)
    {
        $cp->load(['guru', 'subject', 'tujuanPembelajarans']);

        $subjectName = Str::slug($cp->subject?->name ?? 'mapel');
        $cpKode = $cp->kode_cp ?? '1';
        $filename = "dokumen-capaian-pembelajaran-{$subjectName}-cp-{$cpKode}.pdf";

        return $this->generatePdf('pdf.cp_document', compact('cp'), 'a4', 'portrait', $filename);
    }

    /**
     * 6. Cetak Rekap Capaian Pembelajaran per Mapel / Tingkat
     */
    public function cpRekapPdf(Request $request)
    {
        $query = CapaianPembelajaran::with(['guru', 'subject', 'tujuanPembelajarans'])
            ->orderBy('tingkat')
            ->orderBy('id');

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }
        if ($request->filled('tingkat')) {
            $query->where('tingkat', $request->query('tingkat'));
        }

        $cps = $query->get();
        $filename = "rekap-capaian-pembelajaran-" . date('Y-m-d') . ".pdf";

        return $this->generatePdf('pdf.cp_rekap', compact('cps'), 'a4', 'portrait', $filename);
    }

    /**
     * 7. Cetak Rekap Tujuan Pembelajaran (TP)
     */
    public function tpRekapPdf(Request $request)
    {
        $query = TujuanPembelajaran::with(['guru', 'subject', 'capaianPembelajaran'])
            ->orderBy('kelas_tingkat')
            ->orderBy('capaian_pembelajaran_id')
            ->orderBy('id');

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }
        if ($request->filled('kelas_tingkat')) {
            $query->where('kelas_tingkat', $request->query('kelas_tingkat'));
        }
        if ($request->filled('capaian_pembelajaran_id')) {
            $query->where('capaian_pembelajaran_id', $request->query('capaian_pembelajaran_id'));
        }

        $tps = $query->get();
        $filename = "rekap-tujuan-pembelajaran-" . date('Y-m-d') . ".pdf";

        return $this->generatePdf('pdf.tp_rekap', compact('tps'), 'a4', 'landscape', $filename);
    }

    /**
     * 8. Cetak Biodata Lengkap Siswa (Buku Induk Siswa)
     */
    public function studentPdf(Student $student)
    {
        $student->load(['classroom', 'institution', 'characterScoreLogs' => fn ($q) => $q->latest()->limit(5)]);

        $studentName = Str::slug($student->name);
        $nis = $student->nis ?? 'tanpa-nis';
        $filename = "biodata-siswa-{$nis}-{$studentName}.pdf";

        return $this->generatePdf('pdf.student_biodata', compact('student'), 'a4', 'portrait', $filename);
    }

    /**
     * 9. Cetak Surat Permohonan Cuti / Izin Guru
     */
    public function leaveRequestPdf(TeacherLeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['teacher', 'substituteTeacher', 'wakakurApprover']);

        $teacherName = Str::slug($leaveRequest->teacher?->name ?? 'guru');
        $date = $leaveRequest->tanggal_mulai ? $leaveRequest->tanggal_mulai->format('Y-m-d') : date('Y-m-d');
        $filename = "surat-permohonan-izin-{$teacherName}-{$date}.pdf";

        return $this->generatePdf('pdf.teacher_leave_request', compact('leaveRequest'), 'a4', 'portrait', $filename);
    }

    /**
     * 10. Cetak Notulen Rapat & Berita Acara
     */
    public function meetingMinutePdf(MeetingMinute $meetingMinute)
    {
        $filename = "notulen-rapat-" . Str::slug($meetingMinute->judul) . ".pdf";

        return $this->generatePdf('pdf.meeting_minute', compact('meetingMinute'), 'a4', 'portrait', $filename);
    }

    /**
     * 11. Cetak Lembar Kasus & Catatan Pembinaan Siswa
     */
    public function studentCasePdf(StudentCase $studentCase)
    {
        $studentCase->load(['student.classroom', 'waliKelas']);

        $caseNo = Str::slug($studentCase->nomor_kasus ?? 'kasus');
        $studentName = Str::slug($studentCase->student?->name ?? 'siswa');
        $filename = "lembar-kasus-{$caseNo}-{$studentName}.pdf";

        return $this->generatePdf('pdf.student_case', compact('studentCase'), 'a4', 'portrait', $filename);
    }

    /**
     * 12. Cetak Piagam / Bukti Prestasi Siswa
     */
    public function studentAchievementPdf(StudentAchievement $studentAchievement)
    {
        $studentAchievement->load(['student.classroom']);

        $studentName = Str::slug($studentAchievement->student?->name ?? 'siswa');
        $achievementName = Str::slug($studentAchievement->nama_prestasi ?? 'prestasi');
        $filename = "bukti-prestasi-{$studentName}-{$achievementName}.pdf";

        return $this->generatePdf('pdf.student_achievement', compact('studentAchievement'), 'a4', 'portrait', $filename);
    }

    /**
     * 13. Cetak Lembar Sertifikat & Portofolio Guru
     */
    public function certificatePdf(TeacherCertificate $certificate)
    {
        $certificate->load(['teacher']);

        $teacherName = Str::slug($certificate->teacher?->name ?? 'guru');
        $certName = Str::slug($certificate->nama_kegiatan ?? ($certificate->nama_sertifikat ?? 'sertifikat'));
        $filename = "sertifikat-guru-{$teacherName}-{$certName}.pdf";

        return $this->generatePdf('pdf.teacher_certificate', compact('certificate'), 'a4', 'portrait', $filename);
    }

    /**
     * 14. Cetak Rekapitulasi Presensi & Beban Jam Mengajar Guru Bulanan (Landscape A4)
     */
    public function teacherAttendanceReportPdf(Request $request)
    {
        $month = (int) $request->query('bulan', date('m'));
        $year = (int) $request->query('tahun', date('Y'));
        $teacherId = $request->filled('teacher_id') ? (int) $request->query('teacher_id') : null;

        $data = \App\Services\TeacherAttendanceReportService::getReportData($month, $year, $teacherId);

        $namaBulanSlug = Str::slug($data['namaBulan']);
        $filename = "rekap-presensi-beban-jam-guru-{$namaBulanSlug}.pdf";

        return $this->generatePdf('pdf.teacher_attendance_report', $data, 'a4', 'landscape', $filename);
    }

    /**
     * 15. Cetak Rekapitulasi Skor Poin Pelanggaran Siswa (Harian, Mingguan, Bulanan, Triwulan, Semester)
     */
    public function violationRecapPdf(Request $request)
    {
        $tab = $request->query('tab', 'harian');
        $classroomId = $request->filled('classroom_id') ? (int) $request->query('classroom_id') : null;
        $classroom = $classroomId ? Classroom::with('waliKelas')->find($classroomId) : null;

        $user = auth()->user();
        $institutionName = 'SMP ISLAM BAITUL MAKMUR MALANG';

        $defaultBk = User::role('bk')->first();
        $defaultKesiswaan = User::role('waka_kesiswaan')->first();
        $defaultKepsek = User::role('kepala_sekolah')->first();

        $bulanHeaders = [];
        $data = collect();
        $approval = null;
        $docTitle = 'Rekapitulasi Skor Poin Pelanggaran';
        $subTitle = 'Lembaga Pendidikan Islam Terpadu Baitul Makmur Malang';
        $periodeLabel = '-';
        $orientation = 'landscape';
        $paper = 'a4';
        $filename = 'rekap-pelanggaran.pdf';

        if ($tab === 'harian') {
            $tanggal = $request->query('tanggal', date('Y-m-d'));
            $data = StudentViolationReportService::getHarianData($tanggal, $classroomId);
            $approval = StudentViolationApproval::where('classroom_id', $classroomId)
                ->where('periode_type', 'harian')
                ->where('periode_key', $tanggal)
                ->first();

            $docTitle = 'Jurnal Catatan Pelanggaran Harian Siswa (Guru Piket)';
            $periodeLabel = Carbon::parse($tanggal)->translatedFormat('l, d F Y');
            $orientation = 'portrait';
            $filename = "jurnal-pelanggaran-harian-{$tanggal}.pdf";
        } elseif ($tab === 'mingguan') {
            $tahun = (int) $request->query('tahun', date('Y'));
            $bulan = (int) $request->query('bulan', date('m'));
            $minggu = (int) $request->query('minggu', 1);
            $periodeKey = "{$tahun}-M{$bulan}-W{$minggu}";

            $data = StudentViolationReportService::getMingguanData($tahun, $bulan, $minggu, $classroomId);
            $approval = StudentViolationApproval::where('classroom_id', $classroomId)
                ->where('periode_type', 'mingguan')
                ->where('periode_key', $periodeKey)
                ->first();

            [$startDate, $endDate] = StudentViolationReportService::getWeekDateRange($tahun, $bulan, $minggu);
            $docTitle = 'Rekapitulasi Poin Pelanggaran Murid Mingguan';
            $periodeLabel = "Minggu ke-{$minggu} ({$startDate->translatedFormat('d M')} - {$endDate->translatedFormat('d M Y')})";
            $orientation = 'landscape';
            $filename = "rekap-pelanggaran-mingguan-{$periodeKey}.pdf";
        } elseif ($tab === 'bulanan') {
            $tahun = (int) $request->query('tahun', date('Y'));
            $bulan = (int) $request->query('bulan', date('m'));
            $periodeKey = sprintf('%04d-%02d', $tahun, $bulan);

            $data = StudentViolationReportService::getBulananData($tahun, $bulan, $classroomId);
            $approval = StudentViolationApproval::where('classroom_id', $classroomId)
                ->where('periode_type', 'bulanan')
                ->where('periode_key', $periodeKey)
                ->first();

            $docTitle = 'Rekapitulasi Poin Pelanggaran Murid Bulanan';
            $periodeLabel = Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y');
            $orientation = 'landscape';
            $filename = "rekap-pelanggaran-bulanan-{$periodeKey}.pdf";
        } elseif ($tab === 'triwulan') {
            $tahun = (int) $request->query('tahun', date('Y'));
            $triwulan = (int) $request->query('triwulan', 1);

            $result = StudentViolationReportService::getTriwulanData($tahun, $triwulan, $classroomId);
            $data = $result['rows'];
            $bulanHeaders = $result['monthNames'];
            $periodeKey = $result['periodeKey'];

            $approval = StudentViolationApproval::where('classroom_id', $classroomId)
                ->where('periode_type', 'triwulan')
                ->where('periode_key', $periodeKey)
                ->first();

            $docTitle = 'Rekapitulasi Poin Pelanggaran Murid Triwulan (3 Bulan)';
            $periodeLabel = "Triwulan {$triwulan} ({$bulanHeaders[0]} - {$bulanHeaders[2]} {$tahun})";
            $orientation = 'landscape';
            $filename = "rekap-pelanggaran-triwulan-{$periodeKey}.pdf";
        } elseif ($tab === 'semester') {
            $semester = $request->query('semester', 'ganjil');
            $tahunAjaran = $request->query('tahun_ajaran', date('Y') . '/' . (date('Y') + 1));

            $result = StudentViolationReportService::getSemesterData($tahunAjaran, $semester, $classroomId);
            $data = $result['rows'];
            $bulanHeaders = $result['monthNames'];
            $periodeKey = $result['periodeKey'];

            $approval = StudentViolationApproval::where('classroom_id', $classroomId)
                ->where('periode_type', 'semester')
                ->where('periode_key', $periodeKey)
                ->first();

            $docTitle = 'Rekapitulasi Poin Pelanggaran Murid 1 Semester & Predikat Sikap';
            $periodeLabel = "Semester " . ucfirst($semester) . " TP {$tahunAjaran}";
            $orientation = 'landscape';
            $filename = "rekap-pelanggaran-semester-{$semester}-" . Str::slug($tahunAjaran) . ".pdf";
        }

        $viewData = [
            'tab'              => $tab,
            'data'             => $data,
            'classroom'        => $classroom,
            'approval'         => $approval,
            'docTitle'         => $docTitle,
            'subTitle'         => $subTitle,
            'periodeLabel'     => $periodeLabel,
            'institutionName'  => $institutionName,
            'defaultBk'        => $defaultBk,
            'defaultKesiswaan' => $defaultKesiswaan,
            'defaultKepsek'    => $defaultKepsek,
            'bulanHeaders'     => $bulanHeaders,
            'currentUser'      => $user,
            'selectedTanggal'  => $request->query('tanggal', date('Y-m-d')),
        ];

        return $this->generatePdf('pdf.violation_rekap_document', $viewData, $paper, $orientation, $filename);
    }
}
