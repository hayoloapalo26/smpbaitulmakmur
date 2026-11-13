<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentViolationApproval;
use App\Models\StudentViolationLog;
use App\Models\StudentViolationRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentViolationReportService
{
    /**
     * Hitung tanggal mulai dan akhir untuk minggu ke-N dalam suatu bulan.
     */
    public static function getWeekDateRange(int $year, int $month, int $week): array
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $startDay = max(1, ($week - 1) * 7 + 1);
        $endDay = min($daysInMonth, $week * 7);

        $startDate = Carbon::create($year, $month, $startDay)->startOfDay();
        $endDate = Carbon::create($year, $month, $endDay)->endOfDay();

        return [$startDate, $endDate];
    }

    /**
     * Ambil nama bulan dalam bahasa Indonesia.
     */
    public static function getIndonesianMonthName(int $month): string
    {
        return Carbon::create(2026, $month, 1)->translatedFormat('F');
    }

    /**
     * 1. Data Jurnal Catatan Pelanggaran Harian (Guru Piket)
     */
    public static function getHarianData(string $tanggal, ?int $classroomId = null): Collection
    {
        $query = StudentViolationLog::whereDate('tanggal', $tanggal)
            ->with(['student.classroom', 'classroom', 'teacher']);

        if ($classroomId) {
            $query->where(function ($q) use ($classroomId) {
                $q->where('classroom_id', $classroomId)
                  ->orWhereHas('student', fn ($sq) => $sq->where('classroom_id', $classroomId));
            });
        }

        return $query->orderBy('jam', 'asc')->orderBy('id', 'asc')->get();
    }

    /**
     * 2. Data Rekapitulasi Mingguan Seluruh Siswa
     */
    public static function getMingguanData(int $year, int $month, int $week, ?int $classroomId = null): Collection
    {
        [$startDate, $endDate] = self::getWeekDateRange($year, $month, $week);
        $periodeKey = "{$year}-M{$month}-W{$week}";

        $studentsQuery = Student::with('classroom')->orderBy('name');
        if ($classroomId) {
            $studentsQuery->where('classroom_id', $classroomId);
        }
        $students = $studentsQuery->get();

        // Ambil seluruh violation log dalam range minggu ini
        $logs = StudentViolationLog::whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($classroomId, fn ($q) => $q->where('classroom_id', $classroomId))
            ->get()
            ->groupBy('student_id');

        // Ambil records (catatan wali kelas)
        $records = StudentViolationRecord::where('periode_type', 'mingguan')
            ->where('periode_key', $periodeKey)
            ->get()
            ->keyBy('student_id');

        return $students->map(function ($student) use ($logs, $records) {
            $studentLogs = $logs->get($student->id, collect());
            $jenisPelanggaran = $studentLogs->pluck('bentuk_pelanggaran')->filter()->implode(' | ');
            $totalPoin = (int) $studentLogs->sum('poin');

            $tindakLanjutPiket = $studentLogs->pluck('tindak_lanjut_langsung')->filter()->unique()->implode('; ');

            $record = $records->get($student->id);

            return [
                'student'            => $student,
                'jenis_pelanggaran'  => $jenisPelanggaran ?: '-',
                'total_poin'         => $totalPoin,
                'catatan_wali_kelas' => $record?->catatan_wali_kelas ?: '-',
                'tindak_lanjut_piket'=> $tindakLanjutPiket ?: '-',
            ];
        });
    }

    /**
     * 3. Data Rekapitulasi Bulanan Seluruh Siswa
     */
    public static function getBulananData(int $year, int $month, ?int $classroomId = null): Collection
    {
        $currentMonthStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $currentMonthEnd = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $lastMonthObj = Carbon::create($year, $month, 1)->subMonth();
        $lastMonthStart = $lastMonthObj->startOfMonth()->toDateString();
        $lastMonthEnd = $lastMonthObj->endOfMonth()->toDateString();

        $periodeKey = sprintf('%04d-%02d', $year, $month);

        $studentsQuery = Student::with('classroom')->orderBy('name');
        if ($classroomId) {
            $studentsQuery->where('classroom_id', $classroomId);
        }
        $students = $studentsQuery->get();

        // Poin bulan lalu
        $lastMonthLogs = StudentViolationLog::whereBetween('tanggal', [$lastMonthStart, $lastMonthEnd])
            ->get()
            ->groupBy('student_id');

        // Poin bulan ini
        $thisMonthLogs = StudentViolationLog::whereBetween('tanggal', [$currentMonthStart, $currentMonthEnd])
            ->get()
            ->groupBy('student_id');

        // Records BK
        $records = StudentViolationRecord::where('periode_type', 'bulanan')
            ->where('periode_key', $periodeKey)
            ->get()
            ->keyBy('student_id');

        return $students->map(function ($student) use ($lastMonthLogs, $thisMonthLogs, $records) {
            $poinBulanLalu = (int) $lastMonthLogs->get($student->id, collect())->sum('poin');
            $poinBulanIni = (int) $thisMonthLogs->get($student->id, collect())->sum('poin');
            $totalAkumulasi = $poinBulanLalu + $poinBulanIni;

            $record = $records->get($student->id);

            return [
                'student'          => $student,
                'poin_bulan_lalu'  => $poinBulanLalu,
                'poin_bulan_ini'   => $poinBulanIni,
                'total_akumulasi'  => $totalAkumulasi,
                'status_sanksi_bk' => $record?->status_sanksi_bk ?: '-',
                'tindak_lanjut_bk' => $record?->tindak_lanjut_bk ?: '-',
            ];
        });
    }

    /**
     * 4. Data Rekapitulasi Triwulan Seluruh Siswa (3 Bulan)
     */
    public static function getTriwulanData(int $year, int $triwulan, ?int $classroomId = null): array
    {
        // Pemetaan bulan per triwulan:
        // TW 1: 7, 8, 9 (Juli, Agustus, September)
        // TW 2: 10, 11, 12 (Oktober, November, Desember)
        // TW 3: 1, 2, 3 (Januari, Februari, Maret)
        // TW 4: 4, 5, 6 (April, Mei, Juni)
        $monthNumbers = match ($triwulan) {
            2 => [10, 11, 12],
            3 => [1, 2, 3],
            4 => [4, 5, 6],
            default => [7, 8, 9], // Default TW 1: Juli, Agustus, September
        };

        $monthNames = array_map(fn ($m) => self::getIndonesianMonthName($m), $monthNumbers);
        $periodeKey = "{$year}-TW{$triwulan}";

        $studentsQuery = Student::with('classroom')->orderBy('name');
        if ($classroomId) {
            $studentsQuery->where('classroom_id', $classroomId);
        }
        $students = $studentsQuery->get();

        // Hitung poin tiap bulan
        $monthlySums = [];
        foreach ($monthNumbers as $idx => $m) {
            $mYear = ($triwulan >= 3 && $m <= 6) ? ($year + 1) : $year;
            $start = Carbon::create($mYear, $m, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($mYear, $m, 1)->endOfMonth()->toDateString();

            $monthlySums[$idx] = StudentViolationLog::whereBetween('tanggal', [$start, $end])
                ->get()
                ->groupBy('student_id');
        }

        $records = StudentViolationRecord::where('periode_type', 'triwulan')
            ->where('periode_key', $periodeKey)
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function ($student) use ($monthlySums, $records) {
            $p1 = (int) $monthlySums[0]->get($student->id, collect())->sum('poin');
            $p2 = (int) $monthlySums[1]->get($student->id, collect())->sum('poin');
            $p3 = (int) $monthlySums[2]->get($student->id, collect())->sum('poin');
            $total = $p1 + $p2 + $p3;

            $record = $records->get($student->id);

            return [
                'student'                 => $student,
                'poin_bulan_1'            => $p1,
                'poin_bulan_2'            => $p2,
                'poin_bulan_3'            => $p3,
                'total_3_bulan'           => $total,
                'status_sanksi_kesiswaan' => $record?->status_sanksi_kesiswaan ?: '-',
                'tindak_lanjut_kesiswaan' => $record?->tindak_lanjut_kesiswaan ?: '-',
            ];
        });

        return [
            'monthNames' => $monthNames,
            'rows'       => $rows,
            'periodeKey' => $periodeKey,
        ];
    }

    /**
     * 5. Data Rekapitulasi 1 Semester Seluruh Siswa (6 Bulan) & Predikat Sikap
     */
    public static function getSemesterData(string $tahunAjaran, string $semester, ?int $classroomId = null): array
    {
        // Parsing tahun ajaran, misal "2026/2027" -> 2026
        $years = explode('/', $tahunAjaran);
        $baseYear = (int) ($years[0] ?? 2026);

        if ($semester === 'genap') {
            $monthNumbers = [1, 2, 3, 4, 5, 6];
            $calcYear = $baseYear + 1;
        } else {
            // Semester Ganjil: Juli - Desember
            $monthNumbers = [7, 8, 9, 10, 11, 12];
            $calcYear = $baseYear;
        }

        $monthNames = array_map(fn ($m) => self::getIndonesianMonthName($m), $monthNumbers);
        $periodeKey = "{$tahunAjaran}-{$semester}";

        $studentsQuery = Student::with('classroom')->orderBy('name');
        if ($classroomId) {
            $studentsQuery->where('classroom_id', $classroomId);
        }
        $students = $studentsQuery->get();

        $monthlySums = [];
        foreach ($monthNumbers as $idx => $m) {
            $start = Carbon::create($calcYear, $m, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($calcYear, $m, 1)->endOfMonth()->toDateString();

            $monthlySums[$idx] = StudentViolationLog::whereBetween('tanggal', [$start, $end])
                ->get()
                ->groupBy('student_id');
        }

        $records = StudentViolationRecord::where('periode_type', 'semester')
            ->where('periode_key', $periodeKey)
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function ($student) use ($monthlySums, $records) {
            $p1 = (int) $monthlySums[0]->get($student->id, collect())->sum('poin');
            $p2 = (int) $monthlySums[1]->get($student->id, collect())->sum('poin');
            $p3 = (int) $monthlySums[2]->get($student->id, collect())->sum('poin');
            $p4 = (int) $monthlySums[3]->get($student->id, collect())->sum('poin');
            $p5 = (int) $monthlySums[4]->get($student->id, collect())->sum('poin');
            $p6 = (int) $monthlySums[5]->get($student->id, collect())->sum('poin');
            $total = $p1 + $p2 + $p3 + $p4 + $p5 + $p6;

            $predikat = StudentViolationLog::getPredikatNilaiSikap($total);
            $record = $records->get($student->id);

            return [
                'student'            => $student,
                'poin_m1'            => $p1,
                'poin_m2'            => $p2,
                'poin_m3'            => $p3,
                'poin_m4'            => $p4,
                'poin_m5'            => $p5,
                'poin_m6'            => $p6,
                'total_semester'     => $total,
                'predikat'           => $predikat,
                'catatan_wali_kelas' => $record?->catatan_wali_kelas ?: '-',
            ];
        });

        return [
            'monthNames' => $monthNames,
            'rows'       => $rows,
            'periodeKey' => $periodeKey,
        ];
    }
}
