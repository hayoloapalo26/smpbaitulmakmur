<?php

namespace App\Livewire;

use Filament\Widgets\Widget;
use App\Models\Student;
use App\Models\Institution;
use App\Models\ParentDailyChecklist;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ParentActiveness extends Widget
{
    protected static string $view = 'livewire.parent-activeness';
    
    protected int | string | array $columnSpan = 'full';
    
    public $startDate;
    public $endDate;
    public $selectedInstitutionId = null;
    public $showInstitutionModal = false;
    public $showDetailModal = false;

    public function mount()
    {
        // Default to last 7 days for better trend view
        $this->startDate = Carbon::today()->subDays(6)->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
    }

    private function getDaysInRange()
    {
        if (!$this->startDate || !$this->endDate) return 1;
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        if ($end->lessThan($start)) return 1;
        return $start->diffInDays($end) + 1;
    }

    public function getGlobalPercentageProperty()
    {
        $totalStudents = Student::count();
        if ($totalStudents == 0) return 0;

        $days = $this->getDaysInRange();
        $totalExpected = $totalStudents * $days;

        // Count unique student-day combinations
        $activeRecords = ParentDailyChecklist::whereBetween('tanggal', [$this->startDate, $this->endDate])
                            ->select('student_id', 'tanggal')
                            ->distinct()
                            ->get()
                            ->count();

        return round(($activeRecords / $totalExpected) * 100);
    }

    public function getInstitutionsDataProperty()
    {
        $institutions = Institution::all();
        $data = [];
        $days = $this->getDaysInRange();
        
        foreach ($institutions as $inst) {
            if (str_contains(strtolower($inst->name), 'smp')) {
                $totalStudents = Student::count();
                $activeRecords = ParentDailyChecklist::whereBetween('tanggal', [$this->startDate, $this->endDate])
                                    ->select('student_id', 'tanggal')
                                    ->distinct()
                                    ->get()
                                    ->count();
            } else {
                $totalStudents = 0;
                $activeRecords = 0;
            }
            
            $totalExpected = $totalStudents * $days;
            
            $data[] = [
                'id' => $inst->id,
                'name' => $inst->name,
                'total_students' => $totalStudents,
                'active_records' => $activeRecords,
                'expected_records' => $totalExpected,
                'percentage' => $totalExpected > 0 ? round(($activeRecords / $totalExpected) * 100) : 0
            ];
        }
        
        return $data;
    }
    
    public function openInstitutionsModal()
    {
        $this->showInstitutionModal = true;
        $this->showDetailModal = false;
    }
    
    public function viewInstitutionDetail($id)
    {
        $this->selectedInstitutionId = $id;
        $this->showInstitutionModal = false;
        $this->showDetailModal = true;
    }
    
    public function closeModals()
    {
        $this->showInstitutionModal = false;
        $this->showDetailModal = false;
    }
    
    public function getInstitutionDetailProperty()
    {
        if (!$this->selectedInstitutionId) return null;
        
        $inst = Institution::find($this->selectedInstitutionId);
        
        if (str_contains(strtolower($inst->name), 'smp')) {
            $students = Student::with('classroom')->get();
        } else {
            $students = collect([]);
        }
        
        $days = $this->getDaysInRange();

        // Get count of active days for each student in the date range
        $studentActivityCounts = ParentDailyChecklist::whereBetween('tanggal', [$this->startDate, $this->endDate])
                            ->select('student_id', DB::raw('COUNT(DISTINCT tanggal) as active_days'))
                            ->groupBy('student_id')
                            ->pluck('active_days', 'student_id')
                            ->toArray();
                            
        return $students->map(function($student) use ($studentActivityCounts, $days) {
            $activeDays = $studentActivityCounts[$student->id] ?? 0;
            $pct = $days > 0 ? round(($activeDays / $days) * 100) : 0;
            return [
                'id' => $student->id,
                'name' => $student->name,
                'classroom' => $student->classroom ? $student->classroom->name : '-',
                'parent_name' => $student->nama_ayah ?? $student->nama_ibu ?? 'Orang Tua',
                'active_days' => $activeDays,
                'total_days' => $days,
                'pct' => $pct,
            ];
        })->sortByDesc('pct')->values();
    }
}