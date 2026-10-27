<?php

namespace App\Filament\Yayasan\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Institution;
use Carbon\Carbon;

class TeacherPerformanceDashboard extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'superadmin',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'tim_pengembang_penjamin_mutu_yayasan',
        ]) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'Yayasan';
    protected static ?string $navigationLabel = 'Ranking Kinerja Guru';
    protected static ?string $title = 'Leaderboard Kinerja & Ketercapaian Tugas Guru';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.yayasan.pages.teacher-performance-dashboard';

    public ?string $activeTab = 'bulanan';
    public ?int $selectedInstitutionId = null;
    public $detailTasks = [];
    public $selectedTeacherName = '';
    public $detailTeacherId = null;
    public $detailPeriode = 'bulanan';

    public function mount()
    {
        $requestedInstitutionId = request()->query('institution_id');
        if ($requestedInstitutionId && Institution::where('id', $requestedInstitutionId)->exists()) {
            $this->selectedInstitutionId = (int) $requestedInstitutionId;
        } else {
            $firstSchool = Institution::where('id', '!=', 1)->first();
            $this->selectedInstitutionId = $firstSchool?->id ?? 2;
        }
    }

    public function getInstitutionsProperty()
    {
        return Institution::where('id', '!=', 1)->orderBy('id')->get();
    }

    public function getLeaderboardDataProperty()
    {
        $kpiService = new \App\Services\TeacherKpiService();

        $teachers = User::where('institution_id', $this->selectedInstitutionId)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_kelas']);
            })->get();

        $leaderboard = collect();
        foreach ($teachers as $teacher) {
            $kpi = $kpiService->calculateForTeacher($teacher->id, $this->activeTab);
            $teacher->persentase = $kpi;
            $teacher->tugas_selesai = $kpiService->getCompletedTasksCount($teacher->id, $this->activeTab);
            $teacher->teacher = clone $teacher;
            $teacher->teacher_id = $teacher->id;
            $leaderboard->push($teacher);
        }

        $leaderboard = $leaderboard->sortByDesc('persentase')->values();

        $rank = 1;
        foreach ($leaderboard as $item) {
            $item->rank = $rank++;
        }

        return $leaderboard;
    }

    public function openDetail($teacherId)
    {
        $this->detailTeacherId = $teacherId;
        $this->detailPeriode = $this->activeTab;
        $teacher = User::find($teacherId);
        $this->selectedTeacherName = $teacher ? $teacher->name : 'Unknown';
        
        $this->loadDetailTasks();
        $this->dispatch('open-modal', id: 'detail-tugas-modal');
    }

    public function updatedDetailPeriode()
    {
        if ($this->detailTeacherId) {
            $this->loadDetailTasks();
        }
    }

    private function loadDetailTasks()
    {
        $mandatoryQuery = \App\Models\MandatoryTaskSubmission::with('mandatoryTask')
            ->where('user_id', $this->detailTeacherId);
        $optionalQuery = \App\Models\OptionalTaskSubmission::with('optionalTask')
            ->where('user_id', $this->detailTeacherId);

        $this->applyPeriodeFilter($mandatoryQuery, $this->detailPeriode, 'submitted_at');
        $this->applyPeriodeFilter($optionalQuery, $this->detailPeriode, 'submitted_at');

        $tasks = collect();
        foreach ($mandatoryQuery->orderByDesc('submitted_at')->get() as $sub) {
            $tasks->push([
                'id' => 'm_' . $sub->id,
                'tanggal' => $sub->submitted_at ? $sub->submitted_at->format('Y-m-d') : now()->format('Y-m-d'),
                'tugas' => '[Wajib] ' . ($sub->mandatoryTask->title ?? 'Tugas Wajib Harian'),
                'is_done' => true,
                'completed_at' => $sub->submitted_at ? $sub->submitted_at->format('Y-m-d H:i:s') : null,
            ]);
        }
        foreach ($optionalQuery->orderByDesc('submitted_at')->get() as $sub) {
            $tasks->push([
                'id' => 'o_' . $sub->id,
                'tanggal' => $sub->submitted_at ? $sub->submitted_at->format('Y-m-d') : now()->format('Y-m-d'),
                'tugas' => '[Opsional] ' . ($sub->optionalTask->title ?? 'Tugas Opsional'),
                'is_done' => true,
                'completed_at' => $sub->submitted_at ? $sub->submitted_at->format('Y-m-d H:i:s') : null,
            ]);
        }

        $this->detailTasks = $tasks->sortByDesc('completed_at')->values()->toArray();
    }

    private function applyPeriodeFilter($query, $periode, string $column = 'tanggal')
    {
        if ($periode === 'harian') {
            $query->whereDate($column, Carbon::today());
        } elseif ($periode === 'mingguan') {
            $query->whereBetween($column, [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($periode === 'bulanan') {
            $query->whereMonth($column, Carbon::now()->month)->whereYear($column, Carbon::now()->year);
        }
    }
}
