<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\TeacherTodoList;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherPerformanceDashboard extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'superadmin',
            'kepala_sekolah',
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

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Staff\Widgets\TeacherRankingTableWidget::class,
        ];
    }

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'Ranking Kinerja Guru';
    protected static ?string $title = 'Leaderboard Kinerja & Ketercapaian Tugas Guru';
    protected static ?int $navigationSort = 11;
    protected static string $view = 'filament.staff.pages.teacher-performance-dashboard';

    public ?string $activeTab = 'bulanan';
    public $detailTasks = [];
    public $selectedTeacherName = '';
    public $detailTeacherId = null;
    public $detailPeriode = 'bulanan';

    public function getTabs(): array
    {
        return [
            'harian' => \Filament\Resources\Components\Tab::make('Harian'),
            'mingguan' => \Filament\Resources\Components\Tab::make('Mingguan'),
            'bulanan' => \Filament\Resources\Components\Tab::make('Bulanan'),
        ];
    }

    public function updatedActiveTab()
    {
        // Triggered by livewire to re-render when tab changes
        $this->detailPeriode = $this->activeTab;
    }

    public function getLeaderboardDataProperty()
    {
        $institutionId = auth()->user()->institution_id;
        $kpiService = new \App\Services\TeacherKpiService();

        $teachers = User::where('institution_id', $institutionId)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', array_merge(['guru_mapel', 'wali_kelas'], User::QURAN_TEACHER_ROLES));
            })->get();

        $leaderboard = collect();
        foreach ($teachers as $teacher) {
            $kpi = $kpiService->calculateForTeacher($teacher->id, $this->activeTab);
            
            // To maintain compatibility with existing blade
            $teacher->persentase = $kpi;
            
            // Fetch completed tasks for display (Wajib + Opsional)
            $teacher->tugas_selesai = $kpiService->getCompletedTasksCount($teacher->id, $this->activeTab);
            
            // Self-reference to keep blade compatibility with $item->teacher->name
            $teacher->teacher = clone $teacher;
            $teacher->teacher_id = $teacher->id;
                
            $leaderboard->push($teacher);
        }

        $leaderboard = $leaderboard->sortByDesc('persentase')->values();

        // Assign Rank
        $rank = 1;
        foreach ($leaderboard as $item) {
            $item->rank = $rank++;
        }

        return $leaderboard;
    }

    public function openDetail($teacherId)
    {
        $this->detailTeacherId = $teacherId;
        $this->detailPeriode = $this->activeTab; // Sinkronkan awal dengan filter utama
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
                'tugas' => '[Tugas Insidentil] ' . ($sub->optionalTask->title ?? 'Tugas Insidentil'),
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
        } elseif ($periode === 'semesteran') {
            $month = Carbon::now()->month;
            $year = Carbon::now()->year;
            if ($month >= 7) {
                $query->whereBetween($column, [Carbon::create($year, 7, 1)->startOfDay(), Carbon::create($year, 12, 31)->endOfDay()]);
            } else {
                $query->whereBetween($column, [Carbon::create($year, 1, 1)->startOfDay(), Carbon::create($year, 6, 30)->endOfDay()]);
            }
        } elseif ($periode === 'tahunan') {
            $query->whereYear($column, Carbon::now()->year);
        }
    }

    public function updatedPeriode()
    {
        // Trigger re-render by Livewire
    }
}
