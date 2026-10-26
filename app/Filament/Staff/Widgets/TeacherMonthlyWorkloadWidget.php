<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Schedule;
use App\Models\TeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class TeacherMonthlyWorkloadWidget extends Widget
{
    protected static string $view = 'filament.staff.widgets.teacher-monthly-workload-widget';
    protected int | string | array $columnSpan = 'full';

    public int $selectedMonth;
    public int $selectedYear;

    public function mount(): void
    {
        $this->selectedMonth = (int) date('m');
        $this->selectedYear = (int) date('Y');
    }

    public function getWorkloadDataProperty(): array
    {
        $month = $this->selectedMonth;
        $year = $this->selectedYear;

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

        // Hitung frekuensi hari dalam bulan yang dipilih
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
            // Hari efektif sekolah (Senin - Jumat)
            if (in_array($cursor->dayOfWeek, [1, 2, 3, 4, 5])) {
                $totalHariAktif++;
            }
            $cursor->addDay();
        }

        // Ambil guru riil (kecuali akun dummy @smp.com dan role non-guru)
        $teachers = User::withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_kelas', 'waka_kurikulum', 'guru_piket']))
                  ->orWhereHas('schedules')
                  ->orWhereHas('teacherAttendances');
            })
            ->where('email', 'not like', '%@smp.com')
            ->whereNotIn('id', [1, 2, 3, 25, 30, 31, 32])
            ->orderBy('name')
            ->get();

        $rows = [];
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
            // 1. Beban mingguan
            $schedules = Schedule::withoutGlobalScopes()->where('teacher_id', $t->id)->get();
            $weeklyJP = $schedules->count();

            // 2. Tanggung jawab wajib bulan ini sesuai kalender hari
            $monthlyTargetJP = 0;
            foreach ($schedules as $s) {
                $monthlyTargetJP += ($dayCounts[$s->hari] ?? 4);
            }

            // 3. Presensi riil di bulan yang dipilih
            $attendances = TeacherAttendance::withoutGlobalScopes()
                ->where('teacher_id', $t->id)
                ->whereYear('tanggal', $year)
                ->whereMonth('tanggal', $month)
                ->get();

            $hadirJP = $attendances->where('status', 'hadir')->count();
            $izinJP = $attendances->where('status', 'izin')->count();
            $sakitJP = $attendances->where('status', 'sakit')->count();
            $alpaJP = $attendances->where('status', 'alpa')->count();
            $dinasJP = $attendances->where('status', 'dinas_luar')->count();

            // 4. Inval / Pengganti (saat ditugaskan menggantikan guru lain)
            $invalJP = TeacherAttendance::withoutGlobalScopes()
                ->where('substitute_teacher_id', $t->id)
                ->whereYear('tanggal', $year)
                ->whereMonth('tanggal', $month)
                ->count();

            // 5. Total Kehadiran Mengajar (Hadir Tatap Muka + Jam Pengganti Inval)
            $totalKehadiranJP = $hadirJP + $invalJP;

            // 6. Selisih dan Persentase
            $selisihJP = $totalKehadiranJP - $monthlyTargetJP;
            $persen = $monthlyTargetJP > 0
                ? round(($totalKehadiranJP / $monthlyTargetJP) * 100, 1)
                : ($totalKehadiranJP > 0 ? 100 : 0);

            // Akumulasi total
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

            $rows[] = [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
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

        return [
            'rows' => $rows,
            'totals' => $totals,
            'avg_persen' => $avgPersen,
            'total_hari_aktif' => $totalHariAktif,
            'nama_bulan' => Carbon::create($year, $month, 1)->translatedFormat('F Y'),
        ];
    }
}
