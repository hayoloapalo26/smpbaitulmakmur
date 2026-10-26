<?php

namespace App\Filament\Staff\Widgets;

use App\Models\AcademicYear;
use App\Models\DutySchedule;
use App\Models\Schedule;
use App\Services\DutyScheduleGeneratorService;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class DutyScheduleFormationWidget extends Widget
{
    protected static string $view = 'filament.staff.widgets.duty-schedule-formation-widget';
    protected int | string | array $columnSpan = 'full';

    public ?string $semester = null;
    public ?string $todayDay = null;
    public array $formation = [];
    public array $todayTeachers = [];
    public ?string $academicYearName = null;

    public function mount(): void
    {
        $today = Carbon::today();
        $this->todayDay = Schedule::getIndonesianDayName($today);
        $month = $today->month;
        $this->semester = ($month >= 7 && $month <= 12) ? 'Ganjil' : 'Genap';

        $activeAy = AcademicYear::where('is_active', true)->first();
        $this->academicYearName = $activeAy?->name ?? ($today->year . '/' . ($today->year + 1));

        $instId = Auth::user()?->institution_id;
        $this->formation = DutyScheduleGeneratorService::getCurrentWeeklyFormation($instId, $this->semester);

        // Cari guru piket hari ini
        $this->todayTeachers = DutySchedule::whereDate('tanggal', $today)
            ->with('teacher')
            ->get()
            ->pluck('teacher.name')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }
}
