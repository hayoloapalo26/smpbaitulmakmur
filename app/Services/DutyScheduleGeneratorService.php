<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\DutySchedule;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DutyScheduleGeneratorService
{
    /**
     * Dapatkan daftar guru yang memenuhi syarat bertugas piket.
     */
    public static function getEligibleTeachers(?int $institutionId = null): Collection
    {
        $query = User::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('name', '!=', 'Guru Piket')
            ->where('username', '!=', 'gurupiket')
            ->where('email', 'not like', '%@smp.com')
            ->where(function ($q) {
                $q->whereHas('roles', function ($r) {
                    $r->whereIn('name', [
                        'guru_mapel',
                        'guru_alquran',
                        'wali_kelas',
                        'guru_kelas',
                        'bk',
                    ]);
                })->orWhereHas('schedules');
            });

        if ($institutionId && $institutionId != 1) {
            $query->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)
                  ->orWhereNull('institution_id');
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Generate & acak jadwal piket untuk 1 semester.
     *
     * @param array $params
     * @return array
     */
    public function generateSemesterSchedule(array $params): array
    {
        $institutionId = $params['institution_id'] ?? null;
        $academicYearId = $params['academic_year_id'] ?? null;
        $semester = $params['semester'] ?? 'Ganjil';
        $startDateStr = $params['start_date'] ?? null;
        $endDateStr = $params['end_date'] ?? null;
        $daysOfWeek = $params['days_of_week'] ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $teachersPerDay = max(1, (int) ($params['teachers_per_day'] ?? 2));
        $teacherIds = $params['teacher_ids'] ?? [];
        $mode = $params['mode'] ?? 'weekly_fixed';
        $avoidPreviousSemesterDay = $params['avoid_previous_semester_day'] ?? true;
        $overwrite = $params['overwrite'] ?? true;

        if (empty($teacherIds)) {
            $teacherIds = static::getEligibleTeachers($institutionId)->pluck('id')->toArray();
        }

        if (empty($teacherIds)) {
            throw new \InvalidArgumentException('Tidak ada guru yang dipilih atau tersedia untuk jadwal piket.');
        }

        // Tentukan rentang tanggal default jika tidak diberikan
        $activeYear = $academicYearId ? AcademicYear::find($academicYearId) : AcademicYear::where('is_active', true)->first();
        $currentYear = Carbon::now()->year;

        if (!$startDateStr || !$endDateStr) {
            if ($semester === 'Ganjil') {
                $startDateStr = $startDateStr ?: "{$currentYear}-07-01";
                $endDateStr = $endDateStr ?: "{$currentYear}-12-31";
            } else {
                $nextYear = $currentYear + 1;
                $startDateStr = $startDateStr ?: "{$nextYear}-01-01";
                $endDateStr = $endDateStr ?: "{$nextYear}-06-30";
            }
        }

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->endOfDay();

        if ($startDate->gt($endDate)) {
            throw new \InvalidArgumentException('Tanggal mulai semester harus sebelum tanggal selesai semester.');
        }

        // Hapus jadwal sebelumnya di rentang tanggal ini jika overwrite aktif
        if ($overwrite) {
            $deleteQuery = DutySchedule::whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()]);
            if ($institutionId && Schema::hasColumn('duty_schedules', 'institution_id') && $institutionId != 1) {
                $deleteQuery->where('institution_id', $institutionId);
            }
            $deleteQuery->delete();
        }

        $createdCount = 0;
        $formationSummary = [];

        // MODE 1: FORMULASI MINGGUAN TETAP SELAMA 1 SEMESTER (DIACAK TIAP SEMESTER)
        if ($mode === 'weekly_fixed') {
            // Ambil data hari piket guru di semester sebelumnya untuk pengacakan ulang yang adil
            $previousAssignments = [];
            if ($avoidPreviousSemesterDay) {
                $previousAssignments = $this->getPreviousSemesterAssignments($teacherIds, $startDate);
            }

            // Acak pembagian hari untuk masing-masing guru
            $dayAssignments = $this->assignTeachersToDays(
                $teacherIds,
                $daysOfWeek,
                $teachersPerDay,
                $previousAssignments
            );

            // Buat ringkasan nama guru per hari untuk laporan/kembalian
            $teachersMap = User::withoutGlobalScopes()->whereIn('id', $teacherIds)->pluck('name', 'id');
            foreach ($dayAssignments as $dayName => $assignedIds) {
                $formationSummary[$dayName] = array_map(fn ($id) => $teachersMap[$id] ?? "Guru ID #{$id}", $assignedIds);
            }

            // Loop setiap tanggal dalam rentang semester
            $recordsToInsert = [];
            $now = Carbon::now();

            $period = CarbonPeriod::create($startDate, $endDate);
            foreach ($period as $date) {
                $dayName = Schedule::getIndonesianDayName($date);

                if (!in_array($dayName, $daysOfWeek) || empty($dayAssignments[$dayName])) {
                    continue;
                }

                foreach ($dayAssignments[$dayName] as $teacherId) {
                    $row = [
                        'teacher_id' => $teacherId,
                        'tanggal' => $date->toDateString(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (Schema::hasColumn('duty_schedules', 'hari')) {
                        $row['hari'] = $dayName;
                    }
                    if (Schema::hasColumn('duty_schedules', 'semester')) {
                        $row['semester'] = $semester;
                    }
                    if ($academicYearId && Schema::hasColumn('duty_schedules', 'academic_year_id')) {
                        $row['academic_year_id'] = $academicYearId;
                    }
                    if ($institutionId && Schema::hasColumn('duty_schedules', 'institution_id')) {
                        $row['institution_id'] = $institutionId;
                    }

                    $recordsToInsert[] = $row;

                    // Insert per batch 500 records agar hemat memori
                    if (count($recordsToInsert) >= 500) {
                        DutySchedule::insert($recordsToInsert);
                        $createdCount += count($recordsToInsert);
                        $recordsToInsert = [];
                    }
                }
            }

            if (!empty($recordsToInsert)) {
                DutySchedule::insert($recordsToInsert);
                $createdCount += count($recordsToInsert);
            }
        } else {
            // MODE 2: ROTASI BERGILIR (ROUND-ROBIN CYCLIC SHUFFLE)
            $shuffledTeachers = $teacherIds;
            shuffle($shuffledTeachers);
            $teacherIndex = 0;
            $totalTeachers = count($shuffledTeachers);

            $recordsToInsert = [];
            $now = Carbon::now();

            $period = CarbonPeriod::create($startDate, $endDate);
            foreach ($period as $date) {
                $dayName = Schedule::getIndonesianDayName($date);

                if (!in_array($dayName, $daysOfWeek)) {
                    continue;
                }

                for ($i = 0; $i < $teachersPerDay; $i++) {
                    $teacherId = $shuffledTeachers[$teacherIndex % $totalTeachers];
                    $teacherIndex++;

                    $row = [
                        'teacher_id' => $teacherId,
                        'tanggal' => $date->toDateString(),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (Schema::hasColumn('duty_schedules', 'hari')) {
                        $row['hari'] = $dayName;
                    }
                    if (Schema::hasColumn('duty_schedules', 'semester')) {
                        $row['semester'] = $semester;
                    }
                    if ($academicYearId && Schema::hasColumn('duty_schedules', 'academic_year_id')) {
                        $row['academic_year_id'] = $academicYearId;
                    }
                    if ($institutionId && Schema::hasColumn('duty_schedules', 'institution_id')) {
                        $row['institution_id'] = $institutionId;
                    }

                    $recordsToInsert[] = $row;
                }

                if (count($recordsToInsert) >= 500) {
                    DutySchedule::insert($recordsToInsert);
                    $createdCount += count($recordsToInsert);
                    $recordsToInsert = [];
                }
            }

            if (!empty($recordsToInsert)) {
                DutySchedule::insert($recordsToInsert);
                $createdCount += count($recordsToInsert);
            }
        }

        return [
            'success' => true,
            'count' => $createdCount,
            'formation' => $formationSummary,
            'semester' => $semester,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'mode' => $mode,
        ];
    }

    /**
     * Dapatkan riwayat penugasan hari piket guru di semester sebelumnya.
     * Mengembalikan array [teacher_id => 'Senin', ...]
     */
    protected function getPreviousSemesterAssignments(array $teacherIds, Carbon $currentSemesterStart): array
    {
        // Cari jadwal piket 6 bulan sebelum awal semester sekarang
        $prevStart = $currentSemesterStart->copy()->subMonths(7)->toDateString();
        $prevEnd = $currentSemesterStart->copy()->subDay()->toDateString();

        $rows = DutySchedule::whereIn('teacher_id', $teacherIds)
            ->whereBetween('tanggal', [$prevStart, $prevEnd])
            ->select('teacher_id', 'tanggal')
            ->get();

        $teacherDaysCount = [];
        foreach ($rows as $row) {
            $day = Schedule::getIndonesianDayName($row->tanggal);
            $teacherDaysCount[$row->teacher_id][$day] = ($teacherDaysCount[$row->teacher_id][$day] ?? 0) + 1;
        }

        $result = [];
        foreach ($teacherDaysCount as $tId => $days) {
            arsort($days);
            $result[$tId] = array_key_first($days);
        }

        return $result;
    }

    /**
     * Algoritma pengacakan penugasan guru ke hari-hari sekolah dengan menghindari hari semester sebelumnya.
     */
    protected function assignTeachersToDays(
        array $teacherIds,
        array $daysOfWeek,
        int $teachersPerDay,
        array $previousAssignments = []
    ): array {
        $totalDays = count($daysOfWeek);
        if ($totalDays === 0) {
            return [];
        }

        $shuffledTeachers = $teacherIds;
        shuffle($shuffledTeachers);

        $dayAssignments = array_fill_keys($daysOfWeek, []);

        // Total slot yang dibutuhkan
        $totalSlots = $totalDays * $teachersPerDay;

        // Jika jumlah guru lebih sedikit dari total slot yang dibutuhkan,
        // duplikasi daftar guru agar semua slot hari terisi penuh
        $pool = $shuffledTeachers;
        while (count($pool) < $totalSlots) {
            $moreTeachers = $shuffledTeachers;
            shuffle($moreTeachers);
            $pool = array_merge($pool, $moreTeachers);
        }

        // Distribusikan guru ke hari dengan memprioritaskan hari yang BERBEDA dari semester sebelumnya
        foreach ($daysOfWeek as $dayName) {
            while (count($dayAssignments[$dayName]) < $teachersPerDay && !empty($pool)) {
                // Cari guru di pool yang hari semester lalunya BUKAN hari ini
                $foundKey = null;
                foreach ($pool as $k => $candidateId) {
                    $prevDay = $previousAssignments[$candidateId] ?? null;
                    if ($prevDay !== $dayName && !in_array($candidateId, $dayAssignments[$dayName])) {
                        $foundKey = $k;
                        break;
                    }
                }

                // Jika tidak ada kandidat ideal (semua tersisa pernah di hari ini atau sudah ada di hari ini),
                // ambil guru pertama di pool yang belum ditugaskan di hari ini
                if ($foundKey === null) {
                    foreach ($pool as $k => $candidateId) {
                        if (!in_array($candidateId, $dayAssignments[$dayName])) {
                            $foundKey = $k;
                            break;
                        }
                    }
                }

                // Jika masih null, ambil elemen pertama
                if ($foundKey === null) {
                    $foundKey = array_key_first($pool);
                }

                $chosenId = $pool[$foundKey];
                unset($pool[$foundKey]);
                $pool = array_values($pool);

                $dayAssignments[$dayName][] = $chosenId;
            }
        }

        // Jika ada sisa guru dalam pool asli (jumlah guru lebih banyak dari total slot),
        // bagikan sisa guru ini secara merata ke hari-hari yang ada
        if (count($shuffledTeachers) > $totalSlots) {
            $assignedTeacherIds = [];
            foreach ($dayAssignments as $assignedList) {
                $assignedTeacherIds = array_merge($assignedTeacherIds, $assignedList);
            }
            $unassignedTeachers = array_diff($shuffledTeachers, $assignedTeacherIds);

            $dayIndex = 0;
            foreach ($unassignedTeachers as $remainingTeacherId) {
                $targetDay = $daysOfWeek[$dayIndex % $totalDays];
                $dayAssignments[$targetDay][] = $remainingTeacherId;
                $dayIndex++;
            }
        }

        return $dayAssignments;
    }

    /**
     * Dapatkan formasi mingguan rutin jadwal piket saat ini/aktif.
     */
    public static function getCurrentWeeklyFormation(?int $institutionId = null, ?string $semester = null): array
    {
        $today = Carbon::today();
        $currentMonth = $today->month;
        $targetSemester = $semester ?: (($currentMonth >= 7 && $currentMonth <= 12) ? 'Ganjil' : 'Genap');

        // Cari rentang tanggal semester aktif
        $currentYear = $today->year;
        if ($targetSemester === 'Ganjil') {
            $start = Carbon::create($currentYear, 7, 1)->toDateString();
            $end = Carbon::create($currentYear, 12, 31)->toDateString();
        } else {
            $start = Carbon::create($currentYear, 1, 1)->toDateString();
            $end = Carbon::create($currentYear, 6, 30)->toDateString();
        }

        $query = DutySchedule::with('teacher')
            ->where(function ($q) use ($start, $end, $targetSemester) {
                $q->whereBetween('tanggal', [$start, $end])
                  ->orWhereNull('tanggal')
                  ->orWhere('semester', $targetSemester);
            });

        if ($institutionId && Schema::hasColumn('duty_schedules', 'institution_id') && $institutionId != 1) {
            $query->where('institution_id', $institutionId);
        }

        $records = $query->get();

        $days = ['Senin' => [], 'Selasa' => [], 'Rabu' => [], 'Kamis' => [], 'Jumat' => [], 'Sabtu' => []];

        foreach ($records as $record) {
            $dayName = $record->hari;
            if (!isset($days[$dayName])) {
                $days[$dayName] = [];
            }

            $teacherName = $record->teacher?->name;
            if ($teacherName && !in_array($teacherName, $days[$dayName])) {
                $days[$dayName][] = $teacherName;
            }
        }

        // Filter hari-hari yang memiliki data
        return array_filter($days, fn ($list) => !empty($list));
    }
}
