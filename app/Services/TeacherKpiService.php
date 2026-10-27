<?php

namespace App\Services;

use App\Models\User;
use App\Models\Schedule;
use App\Models\TeachingJournal;
use App\Models\TeacherAttendance;
use App\Models\MandatoryTask;
use App\Models\MandatoryTaskSubmission;
use App\Models\OptionalTask;
use App\Models\OptionalTaskSubmission;
use App\Models\TeacherTodoList;
use Carbon\Carbon;

class TeacherKpiService
{
    protected static array $attendanceCache = [];
    protected static array $todoCache = [];
    protected static array $completedTasksCache = [];
    protected static array $teacherKpiCache = [];
    protected static array $institutionAvgCache = [];
    protected static array $userCache = [];
    protected static array $mandatoryTasksCache = [];

    public static function clearCache(): void
    {
        self::$attendanceCache = [];
        self::$todoCache = [];
        self::$completedTasksCache = [];
        self::$teacherKpiCache = [];
        self::$institutionAvgCache = [];
        self::$userCache = [];
        self::$mandatoryTasksCache = [];
    }

    /**
     * Hitung persentase kehadiran guru (0 - 100%) berdasarkan Jadwal Pelajaran & Jurnal Mengajar.
     */
    public function calculateAttendancePercentage(int $teacherId, string $period = 'bulanan'): float
    {
        $cacheKey = "{$teacherId}_{$period}";
        if (isset(self::$attendanceCache[$cacheKey])) {
            return self::$attendanceCache[$cacheKey];
        }

        $now = Carbon::now();
        $queryDateStart = match ($period) {
            'harian'   => $now->copy()->startOfDay(),
            'mingguan' => $now->copy()->startOfWeek(),
            'bulanan'  => $now->copy()->startOfMonth(),
            default    => $now->copy()->startOfMonth(),
        };
        $queryDateEnd = match ($period) {
            'harian'   => $now->copy()->endOfDay(),
            'mingguan' => min($now->copy()->endOfDay(), $now->copy()->endOfWeek()),
            'bulanan'  => min($now->copy()->endOfDay(), $now->copy()->endOfMonth()),
            default    => $now->copy()->endOfDay(),
        };

        $schedules = Schedule::withoutGlobalScopes()
            ->where('teacher_id', $teacherId)
            ->get();

        $journalsCount = TeachingJournal::withoutGlobalScopes()
            ->where('teacher_id', $teacherId)
            ->whereBetween('tanggal', [$queryDateStart->toDateString(), $queryDateEnd->toDateString()])
            ->count();

        $attendanceQuery = TeacherAttendance::withoutGlobalScopes()
            ->where('teacher_id', $teacherId)
            ->whereBetween('tanggal', [$queryDateStart->toDateString(), $queryDateEnd->toDateString()]);

        $totalAttRecords = (clone $attendanceQuery)->count();
        $hadirAttRecords = (clone $attendanceQuery)->whereIn('status', ['masuk', 'hadir'])->count();

        $hadirCount = max($journalsCount, $hadirAttRecords);

        if ($schedules->isNotEmpty()) {
            $expectedSessions = 0;
            $cursor = $queryDateStart->copy()->startOfDay();
            $endDay = $queryDateEnd->copy()->startOfDay();

            while ($cursor->lessThanOrEqualTo($endDay)) {
                $hariName = Schedule::getIndonesianDayName($cursor);
                $daySchedules = $schedules->where('hari', $hariName);

                foreach ($daySchedules as $sched) {
                    $schedCreatedDate = $sched->created_at ? $sched->created_at->copy()->startOfDay() : $queryDateStart->copy()->startOfDay();
                    if ($cursor->greaterThanOrEqualTo($schedCreatedDate)) {
                        $expectedSessions++;
                    }
                }
                $cursor->addDay();
            }

            $totalExpected = max($expectedSessions, $totalAttRecords, $hadirCount);
            if ($totalExpected === 0) {
                return self::$attendanceCache[$cacheKey] = 0;
            }

            return self::$attendanceCache[$cacheKey] = min(100, round(($hadirCount / $totalExpected) * 100, 1));
        }

        // Fallback jika guru belum memiliki jadwal di tabel schedules
        $totalFallback = max($totalAttRecords, $hadirCount);
        $result = $totalFallback > 0 ? min(100, round(($hadirCount / $totalFallback) * 100, 1)) : 0;
        return self::$attendanceCache[$cacheKey] = $result;
    }

    /**
     * Hitung persentase penyelesaian To-Do List Harian (Wajib) & Opsional (0 - 100%).
     */
    public function calculateTodoPercentage(int $teacherId, string $period = 'bulanan'): float
    {
        $cacheKey = "{$teacherId}_{$period}";
        if (isset(self::$todoCache[$cacheKey])) {
            return self::$todoCache[$cacheKey];
        }

        if (!isset(self::$userCache[$teacherId])) {
            self::$userCache[$teacherId] = User::withoutGlobalScopes()->with('roles')->find($teacherId);
        }
        $user = self::$userCache[$teacherId];
        if (!$user) {
            return self::$todoCache[$cacheKey] = 0;
        }

        $now = Carbon::now();
        $queryDateStart = match ($period) {
            'harian'   => $now->copy()->startOfDay(),
            'mingguan' => $now->copy()->startOfWeek(),
            'bulanan'  => $now->copy()->startOfMonth(),
            default    => $now->copy()->startOfMonth(),
        };
        $queryDateEnd = match ($period) {
            'harian'   => $now->copy()->endOfDay(),
            'mingguan' => min($now->copy()->endOfDay(), $now->copy()->endOfWeek()),
            'bulanan'  => min($now->copy()->endOfDay(), $now->copy()->endOfMonth()),
            default    => $now->copy()->endOfDay(),
        };

        $roles = $user->roles->pluck('name')->toArray();
        if (in_array('wali_kelas', $roles) && !in_array('guru_kelas', $roles)) {
            $roles[] = 'guru_kelas';
        }
        if (in_array('guru_kelas', $roles) && !in_array('wali_kelas', $roles)) {
            $roles[] = 'wali_kelas';
        }

        sort($roles);
        $taskCacheKey = ($user->institution_id ?? 0) . '_' . implode('-', $roles);

        if (!isset(self::$mandatoryTasksCache[$taskCacheKey])) {
            $mandatoryTasks = collect();
            if (!empty($roles)) {
                $mandatoryTasks = MandatoryTask::withoutGlobalScopes()
                    ->where('is_active', true)
                    ->when($user->institution_id, fn ($q) => $q->where('institution_id', $user->institution_id))
                    ->where(function ($q) use ($roles) {
                        foreach ($roles as $role) {
                            $q->orWhereJsonContains('target_roles', $role);
                        }
                    })
                    ->get()
                    ->unique('title');
            }
            self::$mandatoryTasksCache[$taskCacheKey] = $mandatoryTasks;
        }
        $mandatoryTasks = self::$mandatoryTasksCache[$taskCacheKey];

        $mandatorySubs = MandatoryTaskSubmission::where('user_id', $teacherId)
            ->whereBetween('submitted_at', [$queryDateStart, $queryDateEnd])
            ->count();

        $optionalSubs = OptionalTaskSubmission::where('user_id', $teacherId)
            ->whereBetween('submitted_at', [$queryDateStart, $queryDateEnd])
            ->count();

        $totalDone = $mandatorySubs + $optionalSubs;

        if ($period === 'harian') {
            $expectedToday = max(1, $mandatoryTasks->count());
            return self::$todoCache[$cacheKey] = min(100, round(($totalDone / $expectedToday) * 100, 1));
        }

        // Untuk mingguan / bulanan: hitung ekspektasi berdasarkan hari aktif
        $activeAttendDays = TeacherAttendance::withoutGlobalScopes()
            ->where('teacher_id', $teacherId)
            ->whereBetween('tanggal', [$queryDateStart->toDateString(), $queryDateEnd->toDateString()])
            ->distinct('tanggal')
            ->count('tanggal');

        $activeSubDays = MandatoryTaskSubmission::where('user_id', $teacherId)
            ->whereBetween('submitted_at', [$queryDateStart, $queryDateEnd])
            ->selectRaw('COUNT(DISTINCT DATE(submitted_at)) as cnt')
            ->value('cnt') ?? 0;

        $activeDays = max($activeAttendDays, (int) $activeSubDays, 1);

        // Jika totalDone == 0, cek fallback ke tabel lama teacher_todo_lists
        if ($totalDone === 0) {
            $legacyTotal = TeacherTodoList::where('teacher_id', $teacherId)
                ->whereBetween('tanggal', [$queryDateStart->toDateString(), $queryDateEnd->toDateString()])
                ->count();
            if ($legacyTotal > 0) {
                $legacyDone = TeacherTodoList::where('teacher_id', $teacherId)
                    ->whereBetween('tanggal', [$queryDateStart->toDateString(), $queryDateEnd->toDateString()])
                    ->where('is_done', true)
                    ->count();
                return self::$todoCache[$cacheKey] = min(100, round(($legacyDone / $legacyTotal) * 100, 1));
            }
            return self::$todoCache[$cacheKey] = 0;
        }

        $expectedTotal = max($activeDays, $totalDone);
        return self::$todoCache[$cacheKey] = min(100, round(($totalDone / $expectedTotal) * 100, 1));
    }

    /**
     * Hitung total tugas (Wajib + Opsional) yang telah diselesaikan oleh guru.
     */
    public function getCompletedTasksCount(int $teacherId, ?string $period = null): int
    {
        $cacheKey = "{$teacherId}_" . ($period ?? 'all');
        if (isset(self::$completedTasksCache[$cacheKey])) {
            return self::$completedTasksCache[$cacheKey];
        }

        $now = Carbon::now();
        $mandatoryQuery = MandatoryTaskSubmission::where('user_id', $teacherId);
        $optionalQuery = OptionalTaskSubmission::where('user_id', $teacherId);

        if ($period === 'harian') {
            $mandatoryQuery->whereDate('submitted_at', $now->toDateString());
            $optionalQuery->whereDate('submitted_at', $now->toDateString());
        } elseif ($period === 'mingguan') {
            $mandatoryQuery->whereBetween('submitted_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
            $optionalQuery->whereBetween('submitted_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
        } elseif ($period === 'bulanan') {
            $mandatoryQuery->whereBetween('submitted_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
            $optionalQuery->whereBetween('submitted_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
        }

        return self::$completedTasksCache[$cacheKey] = $mandatoryQuery->count() + $optionalQuery->count();
    }

    /**
     * Hitung rata-rata KPI seluruh guru dalam satu lembaga.
     */
    public function getInstitutionAverage(int $institutionId, string $period = 'harian'): float
    {
        $cacheKey = "{$institutionId}_{$period}";
        if (isset(self::$institutionAvgCache[$cacheKey])) {
            return self::$institutionAvgCache[$cacheKey];
        }

        $teachers = User::with('roles')->where('institution_id', $institutionId)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_kelas']);
            })
            ->get();

        if ($teachers->isEmpty()) {
            return self::$institutionAvgCache[$cacheKey] = 0;
        }

        $totalKpi = 0;
        foreach ($teachers as $teacher) {
            self::$userCache[$teacher->id] = $teacher;
            $totalKpi += $this->calculateForTeacher($teacher->id, $period);
        }

        return self::$institutionAvgCache[$cacheKey] = round($totalKpi / $teachers->count(), 1);
    }

    /**
     * Hitung KPI dinamis untuk satu guru di level sekolah (Bobot 50% Presensi Jurnal : 50% To-Do List Wajib & Opsional)
     */
    public function calculateForTeacher(int $teacherId, string $period = 'harian'): float
    {
        $cacheKey = "{$teacherId}_{$period}";
        if (isset(self::$teacherKpiCache[$cacheKey])) {
            return self::$teacherKpiCache[$cacheKey];
        }

        // 1. Presensi dari Jurnal Mengajar & Jadwal (Bobot 50%)
        $attendancePct = $this->calculateAttendancePercentage($teacherId, $period);
        $attendanceScore = ($attendancePct / 100) * 50;

        // 2. To-Do List Harian Wajib & Opsional (Bobot 50%)
        $todoPct = $this->calculateTodoPercentage($teacherId, $period);
        $todoScore = ($todoPct / 100) * 50;

        return self::$teacherKpiCache[$cacheKey] = round($attendanceScore + $todoScore, 1);
    }
}
