<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\TeacherAttendance;
use App\Models\User;
use Carbon\Carbon;

class TeacherAttendanceReportService
{
    /**
     * Hitung rekapitulasi beban mengajar (JP) dan kehadiran guru bulanan.
     */
    public static function getReportData(int $month, int $year, ?int $selectedTeacherId = null): array
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

        // Hitung kemunculan hari dalam bulan bersangkutan
        $dayCounts = [
            'Senin' => 0,
            'Selasa' => 0,
            'Rabu' => 0,
            'Kamis' => 0,
            'Jumat' => 0,
            'Sabtu' => 0,
            'Minggu' => 0,
        ];

        $cursor = $startOfMonth->copy();
        $totalHariAktif = 0;
        while ($cursor->lte($endOfMonth)) {
            $dayName = Schedule::getIndonesianDayName($cursor);
            if (isset($dayCounts[$dayName])) {
                $dayCounts[$dayName]++;
            }
            if (in_array($cursor->dayOfWeek, [1, 2, 3, 4, 5])) {
                $totalHariAktif++;
            }
            $cursor->addDay();
        }

        // Ambil guru riil (tanpa dummy @smp.com dan tanpa akun Guru Piket)
        $teachersQuery = User::withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->whereIn('name', array_merge(['guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_kelas', 'waka_kurikulum'], User::QURAN_TEACHER_ROLES)))
                  ->orWhereHas('schedules');
            })
            ->where('name', 'not like', '%piket%')
            ->where(fn ($q) => $q->whereNull('username')->orWhere('username', 'not like', '%piket%'))
            ->where('email', 'not like', '%piket%')
            ->where('email', 'not like', '%@smp.com')
            ->whereNotIn('id', [1, 2, 3, 25, 30, 31, 32]);

        if (!empty($selectedTeacherId)) {
            $teachersQuery->where('id', $selectedTeacherId);
        }

        $teachers = $teachersQuery->orderBy('name')->get();

        $reportData = [];
        $totals = [
            'weekly_jp' => 0,
            'monthly_target_jp' => 0,
            'hadir_jp' => 0,
            'inval_jp' => 0,
            'total_kehadiran_jp' => 0,
            'izin_jp' => 0,
            'sakit_jp' => 0,
            'alpa_jp' => 0,
            'dinas_jp' => 0,
            'selisih_jp' => 0,
        ];

        foreach ($teachers as $t) {
            if (str_contains(strtolower($t->name), 'piket') || str_contains(strtolower($t->username ?? ''), 'piket') || str_contains(strtolower($t->email ?? ''), 'piket')) {
                continue;
            }
            $schedules = Schedule::withoutGlobalScopes()->where('teacher_id', $t->id)->get();
            $weeklyJP = $schedules->count();

            // Tanggung jawab bulanan (target JP wajib sesuai kalender aktif)
            $monthlyTargetJP = 0;
            foreach ($schedules as $s) {
                $monthlyTargetJP += ($dayCounts[$s->hari] ?? 4);
            }

            $attendances = TeacherAttendance::withoutGlobalScopes()
                ->where('teacher_id', $t->id)
                ->whereYear('tanggal', $year)
                ->whereMonth('tanggal', $month)
                ->get();

            $hadirJP = $attendances->whereIn('status', ['masuk', 'hadir'])->count();
            $izinJP = $attendances->where('status', 'izin')->count();
            $sakitJP = $attendances->where('status', 'sakit')->count();
            $alpaJP = $attendances->where('status', 'alpa')->count();
            $dinasJP = $attendances->where('status', 'dinas_luar')->count();

            $invalJP = TeacherAttendance::withoutGlobalScopes()
                ->where('substitute_teacher_id', $t->id)
                ->whereYear('tanggal', $year)
                ->whereMonth('tanggal', $month)
                ->count();

            $totalKehadiranJP = $hadirJP + $invalJP;
            $selisihJP = $totalKehadiranJP - $monthlyTargetJP;
            $persen = $monthlyTargetJP > 0 ? round(($totalKehadiranJP / $monthlyTargetJP) * 100, 1) : ($totalKehadiranJP > 0 ? 100 : 0);

            $totals['weekly_jp'] += $weeklyJP;
            $totals['monthly_target_jp'] += $monthlyTargetJP;
            $totals['hadir_jp'] += $hadirJP;
            $totals['inval_jp'] += $invalJP;
            $totals['total_kehadiran_jp'] += $totalKehadiranJP;
            $totals['izin_jp'] += $izinJP;
            $totals['sakit_jp'] += $sakitJP;
            $totals['alpa_jp'] += $alpaJP;
            $totals['dinas_jp'] += $dinasJP;
            $totals['selisih_jp'] += $selisihJP;

            $reportData[] = [
                'teacher_id' => $t->id,
                'name' => $t->name,
                'nip' => $t->nuptk ?: ($t->no_induk_yayasan ?: '-'),
                'weekly_jp' => $weeklyJP,
                'monthly_target_jp' => $monthlyTargetJP,
                'hadir_jp' => $hadirJP,
                'inval_jp' => $invalJP,
                'total_kehadiran_jp' => $totalKehadiranJP,
                'izin_jp' => $izinJP,
                'sakit_jp' => $sakitJP,
                'alpa_jp' => $alpaJP,
                'dinas_jp' => $dinasJP,
                'selisih_jp' => $selisihJP,
                'persen' => $persen,
            ];
        }

        $avgPersen = $totals['monthly_target_jp'] > 0
            ? round(($totals['total_kehadiran_jp'] / $totals['monthly_target_jp']) * 100, 1)
            : 0;

        $namaBulan = Carbon::create($year, $month, 1)->translatedFormat('F Y');

        return [
            'month' => $month,
            'year' => $year,
            'namaBulan' => $namaBulan,
            'totalHariAktif' => $totalHariAktif,
            'dayCounts' => $dayCounts,
            'teachers' => $teachers,
            'reportData' => $reportData,
            'totals' => $totals,
            'avgPersen' => $avgPersen,
            'selectedTeacherId' => $selectedTeacherId,
        ];
    }
}
