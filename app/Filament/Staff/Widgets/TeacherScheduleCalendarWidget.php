<?php

namespace App\Filament\Staff\Widgets;

use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TeacherScheduleCalendarWidget extends Widget
{
    protected static string $view = 'filament.staff.widgets.teacher-schedule-calendar-widget';

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public $currentMonth;
    public $currentYear;

    public function mount()
    {
        $this->currentMonth = Carbon::now()->month;
        $this->currentYear = Carbon::now()->year;
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && $user->hasAnyRole(array_merge(['guru_mapel', 'wali_kelas'], \App\Models\User::QURAN_TEACHER_ROLES));
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function getScheduleData(): array
    {
        $user = Auth::user();
        
        // Ambil jadwal guru yang login
        $schedules = Schedule::where('teacher_id', $user->id)->get();
        
        $jpPerHari = [
            'Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0, 'Sabtu' => 0, 'Minggu' => 0,
        ];

        foreach ($schedules as $sched) {
            if (isset($jpPerHari[$sched->hari])) {
                $jpPerHari[$sched->hari]++;
            }
        }

        $date = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $daysInMonth = $date->daysInMonth;
        
        // 1 = Senin, 7 = Minggu
        $firstDayOfWeek = $date->isoWeekday(); 
        
        $calendar = [];
        $totalJpThisMonth = 0;
        $totalJpThisWeek = 0;

        $now = Carbon::now();

        $isoToDayName = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];

        // Pad blank days before the 1st of the month
        for ($i = 1; $i < $firstDayOfWeek; $i++) {
            $calendar[] = [
                'date' => null,
                'jp' => 0,
                'is_today' => false,
            ];
        }

        // Fill actual days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($this->currentYear, $this->currentMonth, $day);
            $dayName = $isoToDayName[$currentDate->isoWeekday()];
            
            $jp = $jpPerHari[$dayName] ?? 0;
            $totalJpThisMonth += $jp;
            
            $isToday = $now->isSameDay($currentDate);
            $isThisWeek = $now->isSameWeek($currentDate);

            if ($isThisWeek) {
                $totalJpThisWeek += $jp;
            }

            $calendar[] = [
                'date' => $day,
                'full_date' => $currentDate->format('Y-m-d'),
                'jp' => $jp,
                'is_today' => $isToday,
            ];
        }

        // Pad blank days after the end of the month
        $lastDayOfWeek = Carbon::create($this->currentYear, $this->currentMonth, $daysInMonth)->isoWeekday();
        if ($lastDayOfWeek < 7) {
            for ($i = $lastDayOfWeek + 1; $i <= 7; $i++) {
                $calendar[] = [
                    'date' => null,
                    'jp' => 0,
                    'is_today' => false,
                ];
            }
        }

        return [
            'calendar' => $calendar,
            'totalJpThisMonth' => $totalJpThisMonth,
            'totalJpThisWeek' => $totalJpThisWeek,
            'monthName' => $date->translatedFormat('F Y'),
        ];
    }
}
