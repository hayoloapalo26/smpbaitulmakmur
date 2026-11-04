<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;

class RaporCalculationService
{
    /**
     * Hitung Nilai Akhir Pengetahuan (NA KI-3).
     * Rumus resmi sesuai standar Excel:
     * NA = (RT-NH + 2 * UTS) / 3
     * Nilai Harian (RT-NH) berbobot 1, UTS berbobot 2, dibagi 3.
     */
    public static function calculateNaPengetahuan(?float $rtNh, ?float $uts): ?float
    {
        if ($rtNh === null && $uts === null) {
            return null;
        }

        $h = (float) ($rtNh ?? 0);
        $u = (float) ($uts ?? 0);

        if ($uts !== null && $rtNh !== null) {
            return round(($h + (2 * $u)) / 3, 2);
        }

        return $uts !== null ? (float) $uts : (float) $h;
    }

    /**
     * Hitung Nilai Akhir Keterampilan (NA KI-4).
     * NA = Rata-rata Nilai Harian / Tugas / Portofolio Keterampilan.
     */
    public static function calculateNaKeterampilan(?float $rtNhKeterampilan): ?float
    {
        return $rtNhKeterampilan !== null ? round((float) $rtNhKeterampilan, 2) : null;
    }

    /**
     * Hitung Nilai Konversi ke Skala 1 - 4 (Permendikbud K13 / SMK / SMP).
     * Rumus resmi dari formula Excel:
     * Konversi = (NA / 15) - 2.66
     */
    public static function calculateKonversi(?float $na): float
    {
        if ($na === null || $na <= 0) {
            return 0.0;
        }

        $val = round(($na / 15) - 2.66, 2);
        return (float) max(0.0, min(4.0, $val));
    }

    /**
     * Tentukan Predikat Huruf (A, A-, B+, B, B-, D).
     * Sesuai ambang batas KKM (70 - 78):
     * >= 95 (Konversi >= 3.68) : A
     * >= 90 (Konversi >= 3.34) : A-
     * >= 86 (Konversi >= 3.01) : B+
     * >= 81 (Konversi >= 2.68) : B
     * >= 70 (Konversi >= 2.00) : B-
     * < 70  (Konversi < 2.00)  : D (Perlu Pembinaan)
     */
    public static function determinePredikat(?float $konversi, ?float $na = null): string
    {
        if ($na === null && $konversi === null) {
            return '-';
        }

        $score = $na !== null ? (float) $na : 0.0;
        $k = $konversi !== null ? (float) $konversi : self::calculateKonversi($score);

        if ($score <= 0 && $k <= 0) {
            return 'D';
        }

        // Ambang batas resmi dari analisis Excel:
        // >= 95 : A
        // >= 90 : A-
        // >= 86 : B+
        // >= 81 : B
        // >= 70 : B-
        // < 70  : D (Perlu Pembinaan)
        if ($score >= 95.0 || $k >= 3.68) {
            return 'A';
        }
        if ($score >= 90.0 || $k >= 3.34) {
            return 'A-';
        }
        if ($score >= 86.0) {
            return 'B+';
        }
        if ($score >= 81.0) {
            return 'B';
        }
        if ($score >= 70.0 || $k >= 2.00) {
            return 'B-';
        }

        return 'D';
    }

    /**
     * Tentukan Predikat Sikap (SB, B, C, K).
     */
    public static function determineSikapPredikat(?float $score): string
    {
        if ($score === null) return 'B';
        if ($score >= 90) return 'SB';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        return 'K';
    }

    /**
     * Generate Deskripsi Otomatis Capaian Kompetensi Mata Pelajaran.
     */
    public static function generateDeskripsi(string $aspek, string $predikat, string $subjectName): string
    {
        $mapel = trim($subjectName);

        if ($aspek === 'pengetahuan') {
            return match ($predikat) {
                'A', 'A-' => "Peserta didik sangat memahami materi dan kompetensi pembelajaran {$mapel}.",
                'B+', 'B' => "Peserta didik memahami materi dan kompetensi pembelajaran {$mapel} dengan baik.",
                'B-'      => "Peserta didik cukup memahami materi dan kompetensi pembelajaran {$mapel}.",
                default   => "Peserta didik belum memahami materi pembelajaran {$mapel} dan perlu pembinaan lebih lanjut.",
            };
        }

        if ($aspek === 'keterampilan') {
            return match ($predikat) {
                'A', 'A-' => "Peserta didik sangat mampu mempraktikkan dan menyajikan tugas serta proyek {$mapel}.",
                'B+', 'B' => "Peserta didik mampu mempraktikkan dan menyajikan tugas serta proyek {$mapel} dengan baik.",
                'B-'      => "Peserta didik cukup mampu mempraktikkan dan menyajikan tugas {$mapel}.",
                default   => "Peserta didik belum mampu mempraktikkan dan menyajikan tugas {$mapel} secara mandiri.",
            };
        }

        // Sikap
        return match ($predikat) {
            'SB' => "Peserta didik sangat baik dalam mengamalkan ajaran agama serta menunjukkan sikap beriman, bertaqwa, jujur, disiplin, santun, dan bertanggung jawab.",
            'B'  => "Peserta didik mampu mengamalkan ajaran agama serta menunjukkan sikap beriman, bertaqwa, jujur, santun, dan kontrol diri.",
            'C'  => "Peserta didik cukup dalam mengamalkan sikap disiplin dan perlu penguatan kontrol diri.",
            default => "Peserta didik perlu pembinaan intensif dalam sikap sosial dan spiritual.",
        };
    }

    /**
     * Dapatkan Data Rapor Lengkap untuk 1 Siswa.
     */
    public static function getRaporData(Student $student, ?int $academicYearId = null, ?string $semester = null): array
    {
        $academicYear = $academicYearId 
            ? AcademicYear::find($academicYearId) 
            : (AcademicYear::where('is_active', true)->first() ?? AcademicYear::latest()->first());

        $semester = $semester ?? 'Ganjil';
        $instId = $student->institution_id;

        // 1. Ambil seluruh mata pelajaran yang diajarkan pada lembaga siswa secara dinamis
        $subjects = Subject::query()
            ->when($instId && $instId > 1, fn ($q) => $q->where('institution_id', $instId))
            ->orderBy('name')
            ->get();

        // 2. Ambil seluruh data nilai siswa pada periode ini
        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->get();

        $subjectRows = [];
        $totalNaSum = 0;
        $totalSubjectsWithGrade = 0;

        foreach ($subjects as $idx => $subject) {
            $subGrades = $grades->where('subject_id', $subject->id);

            // Pengetahuan
            $rtNhP = $subGrades->where('type', 'pengetahuan')->first()?->score;
            $uts = $subGrades->where('type', 'ujian')->first()?->score;
            $naP = self::calculateNaPengetahuan($rtNhP, $uts);
            $konversiP = self::calculateKonversi($naP);
            $predikatP = self::determinePredikat($konversiP, $naP);
            $deskripsiP = self::generateDeskripsi('pengetahuan', $predikatP, $subject->name);

            // Keterampilan
            $rtNhK = $subGrades->where('type', 'keterampilan')->first()?->score;
            $naK = self::calculateNaKeterampilan($rtNhK);
            $konversiK = self::calculateKonversi($naK);
            $predikatK = self::determinePredikat($konversiK, $naK);
            $deskripsiK = self::generateDeskripsi('keterampilan', $predikatK, $subject->name);

            // Sikap
            $sikapScore = 85.0; // Nilai default sikap
            $predikatSikap = self::determineSikapPredikat($sikapScore);
            $deskripsiSikap = self::generateDeskripsi('sikap', $predikatSikap, $subject->name);

            if ($naP !== null) {
                $totalNaSum += $naP;
                $totalSubjectsWithGrade++;
            }
            if ($naK !== null) {
                $totalNaSum += $naK;
                $totalSubjectsWithGrade++;
            }

            $subjectRows[] = [
                'no' => $idx + 1,
                'subject' => $subject,
                'name' => $subject->name,
                'kelompok' => $subject->kelompok ?? 'Muatan Nasional',
                'pengetahuan' => [
                    'rt_nh' => $rtNhP,
                    'uts' => $uts,
                    'uts_konversi' => self::calculateKonversi($uts),
                    'uts_predikat' => self::determinePredikat(self::calculateKonversi($uts), $uts),
                    'na' => $naP,
                    'konversi' => $konversiP,
                    'predikat' => $predikatP,
                    'deskripsi' => $deskripsiP,
                ],
                'keterampilan' => [
                    'rt_nh' => $rtNhK,
                    'na' => $naK,
                    'konversi' => $konversiK,
                    'predikat' => $predikatK,
                    'deskripsi' => $deskripsiK,
                ],
                'sikap' => [
                    'score' => $sikapScore,
                    'konversi' => self::calculateKonversi($sikapScore),
                    'predikat' => $predikatSikap,
                    'deskripsi' => $deskripsiSikap,
                ],
            ];
        }

        // 3. Ekstrakurikuler
        $extracurriculars = self::getStudentExtracurriculars($student);

        // 4. Ketidakhadiran
        $attendance = self::getStudentAttendanceSummary($student);

        // 5. Hitung rata-rata & Peringkat Kelas
        $averageNa = $totalSubjectsWithGrade > 0 ? round($totalNaSum / $totalSubjectsWithGrade, 2) : 0;
        $classRank = self::calculateClassRank($student, $academicYear?->id, $semester);

        return [
            'student'          => $student,
            'academic_year'    => $academicYear,
            'semester'         => $semester,
            'subjects'         => $subjectRows,
            'total_na'         => round($totalNaSum, 2),
            'average_na'       => $averageNa,
            'class_rank'       => $classRank['rank'],
            'total_students'   => $classRank['total'],
            'extracurriculars' => $extracurriculars,
            'attendance'       => $attendance,
            'catatan_wali_kelas' => "Ananda menunjukkan perkembangan belajar yang positif, memiliki rasa ingin tahu tinggi, mandiri, disiplin, dan mampu bekerja sama dengan baik.",
            'kkm'              => 75,
        ];
    }

    /**
     * Dapatkan Data Leger Lengkap untuk 1 Kelas (Format Excel Matriks).
     */
    public static function getClassLeger(int $classroomId, ?int $academicYearId = null, ?string $semester = null): array
    {
        $classroom = Classroom::with('waliKelas')->find($classroomId);
        if (!$classroom) {
            return [];
        }

        $academicYear = $academicYearId 
            ? AcademicYear::find($academicYearId) 
            : (AcademicYear::where('is_active', true)->first() ?? AcademicYear::latest()->first());

        $semester = $semester ?? 'Ganjil';
        $students = $classroom->getRealStudents();

        $subjects = Subject::query()
            ->when($classroom->institution_id && $classroom->institution_id > 1, fn ($q) => $q->where('institution_id', $classroom->institution_id))
            ->orderBy('name')
            ->get();

        $studentLegerRows = [];

        foreach ($students as $student) {
            $rapor = self::getRaporData($student, $academicYear?->id, $semester);
            $studentLegerRows[] = $rapor;
        }

        // Urutkan siswa berdasarkan total nilai tertinggi untuk menentukan peringkat akurat
        usort($studentLegerRows, fn ($a, $b) => $b['total_na'] <=> $a['total_na']);

        // Set rank hasil sortir
        foreach ($studentLegerRows as $idx => &$row) {
            $row['class_rank'] = $idx + 1;
        }
        unset($row);

        return [
            'classroom'     => $classroom,
            'academic_year' => $academicYear,
            'semester'      => $semester,
            'subjects'      => $subjects,
            'rows'          => $studentLegerRows,
        ];
    }

    /**
     * Ambil data presensi & nilai ekstrakurikuler siswa.
     */
    protected static function getStudentExtracurriculars(Student $student): array
    {
        $attendances = ExtracurricularAttendance::with('extracurricular')
            ->where('student_id', $student->id)
            ->get();

        if ($attendances->isEmpty()) {
            // Ambil dari master ekstrakurikuler jika ada eskul wajib (misal: Pramuka)
            $defaultEskuls = Extracurricular::where('is_active', true)->take(2)->get();
            $result = [];
            foreach ($defaultEskuls as $eskul) {
                $result[] = [
                    'name' => $eskul->nama,
                    'nilai' => 'A',
                    'deskripsi' => 'Sangat baik, aktif dalam setiap kegiatan dan latihan berkala.',
                ];
            }
            return $result;
        }

        $grouped = $attendances->groupBy('extracurricular_id');
        $result = [];

        foreach ($grouped as $eskulId => $records) {
            $eskul = $records->first()->extracurricular;
            $total = $records->count();
            $hadir = $records->where('status', 'hadir')->count();
            $pct = $total > 0 ? round(($hadir / $total) * 100) : 100;

            $nilai = $pct >= 85 ? 'A' : ($pct >= 75 ? 'B' : 'C');
            $desc = match ($nilai) {
                'A' => 'Sangat baik, aktif dan berprestasi dalam setiap kegiatan.',
                'B' => 'Baik, aktif mengikuti kegiatan dan latihan secara rutin.',
                default => 'Cukup aktif dalam kegiatan ekstrakurikuler.',
            };

            $result[] = [
                'name' => $eskul?->nama ?? 'Ekstrakurikuler',
                'nilai' => $nilai,
                'deskripsi' => $desc,
            ];
        }

        return $result;
    }

    /**
     * Ambil ringkasan absensi harian siswa (Sakit, Izin, Alpa).
     */
    protected static function getStudentAttendanceSummary(Student $student): array
    {
        $daily = DailyAttendance::where('student_id', $student->id)->get();

        return [
            'sakit' => $daily->where('status', 'sakit')->count(),
            'izin'  => $daily->where('status', 'izin')->count(),
            'alpa'  => $daily->whereIn('status', ['alpa', 'tanpa_keterangan'])->count(),
        ];
    }

    /**
     * Hitung Peringkat Siswa di Kelas.
     */
    protected static function calculateClassRank(Student $student, ?int $academicYearId, string $semester): array
    {
        if (!$student->classroom_id) {
            return ['rank' => 1, 'total' => 1];
        }

        $classmates = Student::where('classroom_id', $student->classroom_id)->pluck('id');
        $scores = [];

        foreach ($classmates as $cmId) {
            $sum = Grade::where('student_id', $cmId)
                ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
                ->where('semester', $semester)
                ->sum('score');
            $scores[$cmId] = (float) $sum;
        }

        arsort($scores);
        $rank = 1;
        foreach ($scores as $sId => $val) {
            if ($sId == $student->id) {
                break;
            }
            $rank++;
        }

        return [
            'rank'  => $rank,
            'total' => count($classmates),
        ];
    }
}
