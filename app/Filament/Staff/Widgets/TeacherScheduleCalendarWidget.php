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
        
        // Ambil seluruh jadwal guru yang login beserta relasi kelas dan mata pelajaran
        $schedules = Schedule::where('teacher_id', $user->id)
            ->with(['classroom', 'subject'])
            ->orderBy('jam_ke')
            ->get();
        
        $daysOfWeek = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $scheduleByDay = [];

        foreach ($daysOfWeek as $hari) {
            $daySchedules = $schedules->where('hari', $hari)->sortBy('jam_ke');
            $jpCount = $daySchedules->count();
            
            // Kelompokkan jadwal per kelas dan mata pelajaran
            $classesGrouped = [];
            $scheduleDetails = [];
            
            foreach ($daySchedules as $sched) {
                $cId = $sched->classroom_id ?: 0;
                $cName = $sched->classroom?->name ?? 'Kelas Lain';
                $sName = $sched->subject?->name ?? 'Pelajaran';
                $jamLabel = Schedule::getJamLabelWithTime($sched->jam_ke);
                $jamTime = Schedule::getJamTime($sched->jam_ke) ?? '';
                
                $key = "{$cId}_{$sched->subject_id}";
                if (!isset($classesGrouped[$key])) {
                    $classesGrouped[$key] = [
                        'classroom_id' => $cId,
                        'classroom_name' => $cName,
                        'subject_name' => $sName,
                        'jp' => 0,
                        'jam_numbers' => [],
                    ];
                }
                $classesGrouped[$key]['jp']++;
                if ($sched->jam_ke !== null) {
                    $classesGrouped[$key]['jam_numbers'][] = $sched->jam_ke;
                }
                
                $scheduleDetails[] = [
                    'jam_ke' => $sched->jam_ke,
                    'jam_label' => $jamLabel,
                    'jam_time' => $jamTime,
                    'classroom' => $cName,
                    'subject' => $sName,
                ];
            }
            
            // Format jam mengajar (contoh: "Jam Ke-1, 2")
            foreach ($classesGrouped as &$cg) {
                sort($cg['jam_numbers']);
                $cg['jam_text'] = !empty($cg['jam_numbers'])
                    ? 'Jam Ke-' . implode(', ', $cg['jam_numbers'])
                    : '';
            }
            unset($cg);

            $scheduleByDay[$hari] = [
                'jp' => $jpCount,
                'classes' => array_values($classesGrouped),
                'details' => $scheduleDetails,
            ];
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
                'full_date' => null,
                'day_name' => null,
                'formatted_date' => null,
                'jp' => 0,
                'classes' => [],
                'details' => [],
                'is_today' => false,
            ];
        }

        // Fill actual days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::create($this->currentYear, $this->currentMonth, $day);
            $dayName = $isoToDayName[$currentDate->isoWeekday()];
            
            $dayData = $scheduleByDay[$dayName] ?? ['jp' => 0, 'classes' => [], 'details' => []];
            $jp = $dayData['jp'];
            $totalJpThisMonth += $jp;
            
            $isToday = $now->isSameDay($currentDate);
            $isThisWeek = $now->isSameWeek($currentDate);

            if ($isThisWeek) {
                $totalJpThisWeek += $jp;
            }

            $calendar[] = [
                'date' => $day,
                'full_date' => $currentDate->format('Y-m-d'),
                'day_name' => $dayName,
                'formatted_date' => $currentDate->translatedFormat('l, d F Y'),
                'jp' => $jp,
                'classes' => $dayData['classes'],
                'details' => $dayData['details'],
                'is_today' => $isToday,
            ];
        }

        // Pad blank days after the end of the month
        $lastDayOfWeek = Carbon::create($this->currentYear, $this->currentMonth, $daysInMonth)->isoWeekday();
        if ($lastDayOfWeek < 7) {
            for ($i = $lastDayOfWeek + 1; $i <= 7; $i++) {
                $calendar[] = [
                    'date' => null,
                    'full_date' => null,
                    'day_name' => null,
                    'formatted_date' => null,
                    'jp' => 0,
                    'classes' => [],
                    'details' => [],
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
