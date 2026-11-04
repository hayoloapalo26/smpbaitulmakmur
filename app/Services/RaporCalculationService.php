<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\Grade;
use App\Models\RaporGrade;
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
     * Mode:
     * - 'olah' (default): Menampilkan Nilai Rapor Olahan / Bantuan / Diedit (yang siap dicetak ke Rapor Resmi).
     * - 'asli': Menampilkan Nilai Rapor Asli Murni dari inputan Guru Mapel.
     */
    public static function getRaporData(Student $student, ?int $academicYearId = null, ?string $semester = null, string $mode = 'olah'): array
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

        // 2. Ambil seluruh data nilai murni siswa pada periode ini dari guru mapel
        $grades = Grade::query()
            ->where('student_id', $student->id)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->get();

        // 3. Ambil data penyesuaian / nilai bantuan / nilai olahan rapor jika ada
        $raporAdjustments = RaporGrade::query()
            ->where('student_id', $student->id)
            ->when($academicYear, fn ($q) => $q->where('academic_year_id', $academicYear->id))
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->get()
            ->keyBy('subject_id');

        $subjectRows = [];
        $totalNaSum = 0;
        $totalSubjectsWithGrade = 0;

        $totalNaAsli = 0;
        $totalSubjectsAsli = 0;
        $hasEditedAnySubject = false;

        foreach ($subjects as $idx => $subject) {
            $subGrades = $grades->where('subject_id', $subject->id);
            $adjustment = $raporAdjustments->get($subject->id);

            // --- A. NILAI ASLI (Murni dari Guru Mapel) ---
            $rtNhP_asli = $subGrades->where('type', 'pengetahuan')->first()?->score;
            $uts_asli = $subGrades->where('type', 'ujian')->first()?->score;
            $naP_asli = self::calculateNaPengetahuan($rtNhP_asli, $uts_asli);
            $konversiP_asli = self::calculateKonversi($naP_asli);
            $predikatP_asli = self::determinePredikat($konversiP_asli, $naP_asli);
            $deskripsiP_asli = self::generateDeskripsi('pengetahuan', $predikatP_asli, $subject->name);

            $rtNhK_asli = $subGrades->where('type', 'keterampilan')->first()?->score;
            $naK_asli = self::calculateNaKeterampilan($rtNhK_asli);
            $konversiK_asli = self::calculateKonversi($naK_asli);
            $predikatK_asli = self::determinePredikat($konversiK_asli, $naK_asli);
            $deskripsiK_asli = self::generateDeskripsi('keterampilan', $predikatK_asli, $subject->name);

            if ($naP_asli !== null) {
                $totalNaAsli += $naP_asli;
                $totalSubjectsAsli++;
            }
            if ($naK_asli !== null) {
                $totalNaAsli += $naK_asli;
                $totalSubjectsAsli++;
            }

            // --- B. NILAI OLAHAN / NILAI BANTUAN / DIEDIT ---
            $isEdited = false;
            $catatanBantuan = null;

            if ($adjustment) {
                $naP_olah = $adjustment->nilai_pengetahuan_olah !== null ? (float)$adjustment->nilai_pengetahuan_olah : $naP_asli;
                $naK_olah = $adjustment->nilai_keterampilan_olah !== null ? (float)$adjustment->nilai_keterampilan_olah : $naK_asli;
                $catatanBantuan = $adjustment->catatan_penyesuaian;

                // Tandai jika ada selisih dengan nilai asli atau ada flag custom
                if ($adjustment->is_custom || ($adjustment->nilai_pengetahuan_olah !== null && abs($adjustment->nilai_pengetahuan_olah - ($naP_asli ?? 0)) > 0.01) || ($adjustment->nilai_keterampilan_olah !== null && abs($adjustment->nilai_keterampilan_olah - ($naK_asli ?? 0)) > 0.01)) {
                    $isEdited = true;
                    $hasEditedAnySubject = true;
                }
            } else {
                $naP_olah = $naP_asli;
                $naK_olah = $naK_asli;
            }

            $konversiP_olah = self::calculateKonversi($naP_olah);
            $predikatP_olah = self::determinePredikat($konversiP_olah, $naP_olah);
            $deskripsiP_olah = ($adjustment && $adjustment->deskripsi_pengetahuan) ? $adjustment->deskripsi_pengetahuan : self::generateDeskripsi('pengetahuan', $predikatP_olah, $subject->name);

            $konversiK_olah = self::calculateKonversi($naK_olah);
            $predikatK_olah = self::determinePredikat($konversiK_olah, $naK_olah);
            $deskripsiK_olah = ($adjustment && $adjustment->deskripsi_keterampilan) ? $adjustment->deskripsi_keterampilan : self::generateDeskripsi('keterampilan', $predikatK_olah, $subject->name);

            // --- C. PILIH NILAI AKTIF SESUAI MODE ---
            if ($mode === 'asli') {
                $activeNaP = $naP_asli;
                $activeKonversiP = $konversiP_asli;
                $activePredikatP = $predikatP_asli;
                $activeDeskripsiP = $deskripsiP_asli;

                $activeNaK = $naK_asli;
                $activeKonversiK = $konversiK_asli;
                $activePredikatK = $predikatK_asli;
                $activeDeskripsiK = $deskripsiK_asli;
            } else {
                // Mode olah (default): gunakan nilai bantuan / olahan jika ada
                $activeNaP = $naP_olah;
                $activeKonversiP = $konversiP_olah;
                $activePredikatP = $predikatP_olah;
                $activeDeskripsiP = $deskripsiP_olah;

                $activeNaK = $naK_olah;
                $activeKonversiK = $konversiK_olah;
                $activePredikatK = $predikatK_olah;
                $activeDeskripsiK = $deskripsiK_olah;
            }

            // Sikap
            $sikapScore = 85.0; // Nilai default sikap
            $predikatSikap = self::determineSikapPredikat($sikapScore);
            $deskripsiSikap = self::generateDeskripsi('sikap', $predikatSikap, $subject->name);

            if ($activeNaP !== null) {
                $totalNaSum += $activeNaP;
                $totalSubjectsWithGrade++;
            }
            if ($activeNaK !== null) {
                $totalNaSum += $activeNaK;
                $totalSubjectsWithGrade++;
            }

            $subjectRows[] = [
                'no' => $idx + 1,
                'subject' => $subject,
                'name' => $subject->name,
                'kelompok' => $subject->kelompok ?? 'Muatan Nasional',
                'is_edited' => $isEdited,
                'catatan_bantuan' => $catatanBantuan,

                // Data nilai aktif (untuk cetak dan tampilan rapor)
                'pengetahuan' => [
                    'rt_nh' => $rtNhP_asli,
                    'uts' => $uts_asli,
                    'uts_konversi' => self::calculateKonversi($uts_asli),
                    'uts_predikat' => self::determinePredikat(self::calculateKonversi($uts_asli), $uts_asli),
                    'na' => $activeNaP,
                    'konversi' => $activeKonversiP,
                    'predikat' => $activePredikatP,
                    'deskripsi' => $activeDeskripsiP,
                    'is_edited' => ($mode === 'olah' && $isEdited && $naP_olah != $naP_asli),
                ],
                'keterampilan' => [
                    'rt_nh' => $rtNhK_asli,
                    'na' => $activeNaK,
                    'konversi' => $activeKonversiK,
                    'predikat' => $activePredikatK,
                    'deskripsi' => $activeDeskripsiK,
                    'is_edited' => ($mode === 'olah' && $isEdited && $naK_olah != $naK_asli),
                ],
                'sikap' => [
                    'score' => $sikapScore,
                    'konversi' => self::calculateKonversi($sikapScore),
                    'predikat' => $predikatSikap,
                    'deskripsi' => $deskripsiSikap,
                ],

                // Komparasi Lengkap Asli vs Olahan
                'nilai_asli' => [
                    'pengetahuan' => $naP_asli,
                    'konversi_p'  => $konversiP_asli,
                    'predikat_p'  => $predikatP_asli,
                    'deskripsi_p' => $deskripsiP_asli,
                    'keterampilan'=> $naK_asli,
                    'konversi_k'  => $konversiK_asli,
                    'predikat_k'  => $predikatK_asli,
                    'deskripsi_k' => $deskripsiK_asli,
                    'rt_nh_p'     => $rtNhP_asli,
                    'uts'         => $uts_asli,
                ],
                'nilai_olah' => [
                    'pengetahuan' => $naP_olah,
                    'konversi_p'  => $konversiP_olah,
                    'predikat_p'  => $predikatP_olah,
                    'deskripsi_p' => $deskripsiP_olah,
                    'keterampilan'=> $naK_olah,
                    'konversi_k'  => $konversiK_olah,
                    'predikat_k'  => $predikatK_olah,
                    'deskripsi_k' => $deskripsiK_olah,
                ],
            ];
        }

        // 4. Ekstrakurikuler
        $extracurriculars = self::getStudentExtracurriculars($student, $academicYear?->id, $semester);

        // 5. Ketidakhadiran
        $attendance = self::getStudentAttendanceSummary($student);

        // 6. Hitung rata-rata & Peringkat Kelas
        $averageNa = $totalSubjectsWithGrade > 0 ? round($totalNaSum / $totalSubjectsWithGrade, 2) : 0;
        $classRank = self::calculateClassRank($student, $academicYear?->id, $semester);

        return [
            'student'            => $student,
            'academic_year'      => $academicYear,
            'semester'           => $semester,
            'mode'               => $mode,
            'has_edited_grades'  => $hasEditedAnySubject,
            'subjects'           => $subjectRows,
            'total_na'           => round($totalNaSum, 2),
            'average_na'         => $averageNa,
            'total_na_asli'      => round($totalNaAsli, 2),
            'average_na_asli'    => $totalSubjectsAsli > 0 ? round($totalNaAsli / $totalSubjectsAsli, 2) : 0,
            'class_rank'         => $classRank['rank'],
            'total_students'     => $classRank['total'],
            'extracurriculars'   => $extracurriculars,
            'attendance'         => $attendance,
            'catatan_wali_kelas' => "Ananda menunjukkan perkembangan belajar yang positif, memiliki rasa ingin tahu tinggi, mandiri, disiplin, dan mampu bekerja sama dengan baik.",
            'kkm'                => 75,
        ];
    }

    /**
     * Dapatkan Data Leger Lengkap untuk 1 Kelas (Format Excel Matriks).
     * Mode: 'olah' (Nilai Rapor Resmi/Bantuan) | 'asli' (Nilai Asli Guru Mapel)
     */
    public static function getClassLeger(int $classroomId, ?int $academicYearId = null, ?string $semester = null, string $mode = 'olah'): array
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
            $rapor = self::getRaporData($student, $academicYear?->id, $semester, $mode);
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
            'mode'          => $mode,
            'subjects'      => $subjects,
            'rows'          => $studentLegerRows,
        ];
    }

    /**
     * Simpan / Perbarui Penyesuaian Nilai Bantuan (Olahan) Siswa untuk 1 Mata Pelajaran.
     */
    public static function saveRaporGradeAdjustment(
        int $studentId,
        int $subjectId,
        ?int $academicYearId,
        string $semester,
        ?float $nilaiPOlah,
        ?float $nilaiKOlah,
        ?string $catatan = null,
        ?string $deskripsiP = null,
        ?string $deskripsiK = null,
        ?int $updatedBy = null
    ): RaporGrade {
        $student = Student::find($studentId);
        $subject = Subject::find($subjectId);

        // Ambil nilai asli saat ini sebagai baseline referensi
        $existingGrades = Grade::where([
            'student_id'       => $studentId,
            'subject_id'       => $subjectId,
            'academic_year_id' => $academicYearId,
            'semester'         => $semester,
        ])->get();

        $rtNhP = $existingGrades->where('type', 'pengetahuan')->first()?->score;
        $uts = $existingGrades->where('type', 'ujian')->first()?->score;
        $naP_asli = self::calculateNaPengetahuan($rtNhP, $uts);

        $rtNhK = $existingGrades->where('type', 'keterampilan')->first()?->score;
        $naK_asli = self::calculateNaKeterampilan($rtNhK);

        $isCustom = false;
        if ($nilaiPOlah !== null && $naP_asli !== null && abs($nilaiPOlah - $naP_asli) > 0.01) {
            $isCustom = true;
        }
        if ($nilaiKOlah !== null && $naK_asli !== null && abs($nilaiKOlah - $naK_asli) > 0.01) {
            $isCustom = true;
        }
        if (!empty($catatan)) {
            $isCustom = true;
        }

        $predikatPOlah = $nilaiPOlah !== null ? self::determinePredikat(self::calculateKonversi($nilaiPOlah), $nilaiPOlah) : null;
        $predikatKOlah = $nilaiKOlah !== null ? self::determinePredikat(self::calculateKonversi($nilaiKOlah), $nilaiKOlah) : null;

        $predikatPAsli = $naP_asli !== null ? self::determinePredikat(self::calculateKonversi($naP_asli), $naP_asli) : null;
        $predikatKAsli = $naK_asli !== null ? self::determinePredikat(self::calculateKonversi($naK_asli), $naK_asli) : null;

        return RaporGrade::updateOrCreate(
            [
                'student_id'       => $studentId,
                'subject_id'       => $subjectId,
                'academic_year_id' => $academicYearId,
                'semester'         => $semester,
            ],
            [
                'classroom_id'               => $student?->classroom_id,
                'nilai_pengetahuan_asli'     => $naP_asli,
                'nilai_keterampilan_asli'    => $naK_asli,
                'nilai_akhir_asli'           => ($naP_asli !== null && $naK_asli !== null) ? round(($naP_asli + $naK_asli) / 2, 2) : ($naP_asli ?? $naK_asli),
                'predikat_pengetahuan_asli'  => $predikatPAsli,
                'predikat_keterampilan_asli' => $predikatKAsli,

                'nilai_pengetahuan_olah'     => $nilaiPOlah,
                'nilai_keterampilan_olah'    => $nilaiKOlah,
                'nilai_akhir_olah'           => ($nilaiPOlah !== null && $nilaiKOlah !== null) ? round(($nilaiPOlah + $nilaiKOlah) / 2, 2) : ($nilaiPOlah ?? $nilaiKOlah),
                'predikat_pengetahuan_olah'  => $predikatPOlah,
                'predikat_keterampilan_olah' => $predikatKOlah,

                'deskripsi_pengetahuan'      => $deskripsiP ?: ($subject ? self::generateDeskripsi('pengetahuan', $predikatPOlah ?? 'B', $subject->name) : null),
                'deskripsi_keterampilan'     => $deskripsiK ?: ($subject ? self::generateDeskripsi('keterampilan', $predikatKOlah ?? 'B', $subject->name) : null),

                'is_custom'                  => $isCustom,
                'catatan_penyesuaian'        => $catatan,
                'updated_by'                 => $updatedBy ?? auth()->id(),
                'institution_id'             => $student?->institution_id ?? auth()->user()?->institution_id,
            ]
        );
    }

    /**
     * Katrol Otomatis Nilai Bawah KKM (75) untuk Seluruh Siswa 1 Rombel/Kelas.
     * Nilai asli dari guru mapel tetap aman dan tidak berubah sama sekali.
     */
    public static function autoBoostClassroomToKkm(
        int $classroomId,
        ?int $academicYearId,
        string $semester,
        float $kkm = 75.0,
        ?int $updatedBy = null
    ): int {
        $classroom = Classroom::find($classroomId);
        if (!$classroom) return 0;

        $students = $classroom->getRealStudents();
        $updatedCount = 0;

        foreach ($students as $student) {
            $rapor = self::getRaporData($student, $academicYearId, $semester, 'asli');
            foreach ($rapor['subjects'] as $subData) {
                $sub = $subData['subject'];
                $naP = $subData['pengetahuan']['na'];
                $naK = $subData['keterampilan']['na'];

                $needsBoostP = ($naP !== null && $naP < $kkm);
                $needsBoostK = ($naK !== null && $naK < $kkm);

                if ($needsBoostP || $needsBoostK) {
                    $newP = $needsBoostP ? $kkm : $naP;
                    $newK = $needsBoostK ? $kkm : $naK;

                    self::saveRaporGradeAdjustment(
                        $student->id,
                        $sub->id,
                        $academicYearId,
                        $semester,
                        $newP,
                        $newK,
                        "Nilai Bantuan KKM ({$kkm})",
                        null,
                        null,
                        $updatedBy
                    );
                    $updatedCount++;
                }
            }
        }

        return $updatedCount;
    }

    /**
     * Reset Seluruh Nilai Bantuan Siswa Kembali ke Nilai Asli Murni Guru Mapel.
     */
    public static function resetStudentToAsli(int $studentId, ?int $academicYearId, string $semester): int
    {
        return RaporGrade::where([
            'student_id'       => $studentId,
            'academic_year_id' => $academicYearId,
            'semester'         => $semester,
        ])->delete();
    }

    /**
     * Reset Seluruh Nilai Bantuan Kelas Kembali ke Nilai Asli Murni Guru Mapel.
     */
    public static function resetClassroomToAsli(int $classroomId, ?int $academicYearId, string $semester): int
    {
        $studentIds = Student::where('classroom_id', $classroomId)->pluck('id');
        return RaporGrade::whereIn('student_id', $studentIds)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->where('semester', $semester)
            ->delete();
    }

    /**
     * Ambil data presensi & nilai ekstrakurikuler siswa.
     */
    protected static function getStudentExtracurriculars(Student $student, ?int $academicYearId = null, ?string $semester = null): array
    {
        // 1. Cek nilai akhir semester yang telah diinput oleh Guru Ekstrakurikuler
        $grades = \App\Models\ExtracurricularGrade::with('extracurricular')
            ->where('student_id', $student->id)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($semester, fn ($q) => $q->where('semester', $semester))
            ->get();

        if ($grades->isNotEmpty()) {
            $result = [];
            foreach ($grades as $g) {
                $result[] = [
                    'name' => $g->extracurricular?->name ?? 'Ekstrakurikuler',
                    'nilai' => $g->predikat ?: 'A',
                    'deskripsi' => $g->deskripsi ?: 'Sangat baik, aktif dan berprestasi dalam kegiatan ekstrakurikuler.',
                ];
            }
            return $result;
        }

        // 2. Jika belum ada nilai akhir semester, hitung dari akumulasi kehadiran eskul
        $attendances = ExtracurricularAttendance::with('extracurricular')
            ->where('student_id', $student->id)
            ->get();

        if ($attendances->isEmpty()) {
            // Ambil dari routing anggota eskul jika siswa terdaftar
            $memberships = \App\Models\ExtracurricularMember::with('extracurricular')
                ->where('student_id', $student->id)
                ->where('is_active', true)
                ->get();

            if ($memberships->isNotEmpty()) {
                $res = [];
                foreach ($memberships as $m) {
                    $res[] = [
                        'name' => $m->extracurricular?->name ?? 'Ekstrakurikuler',
                        'nilai' => 'A',
                        'deskripsi' => 'Sangat baik, aktif dalam kegiatan ekstrakurikuler.',
                    ];
                }
                return $res;
            }

            // Fallback default
            $defaultEskuls = Extracurricular::where('is_active', true)->take(2)->get();
            $result = [];
            foreach ($defaultEskuls as $eskul) {
                $result[] = [
                    'name' => $eskul->name,
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
                'name' => $eskul?->name ?? 'Ekstrakurikuler',
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
