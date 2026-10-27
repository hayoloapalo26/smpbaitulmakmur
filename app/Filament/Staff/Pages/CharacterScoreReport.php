<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\CharacterScoreLog;
use Filament\Notifications\Notification;

class CharacterScoreReport extends Page
{
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            'guru_piket', 'wali_kelas', 'guru_kelas', 'waka_kesiswaan', 'kepala_sekolah', 'superadmin'
        ]) || $user->isPiketToday();
    }

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.staff.pages.character-score-report';
    protected static ?string $title = 'Penilaian & Rekapitulasi Karakter Siswa';
    protected static ?string $navigationLabel = 'Penilaian & Rekap Karakter';
    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        $user = Auth::user();
        if ($user && $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return 'Fitur Wali Kelas';
        }
        return 'Kesiswaan & Karakter';
    }

    public ?int $selectedClassroomId = null;
    public ?int $viewStudentId = null;

    // Form Tambah Penilaian Karakter oleh Guru Kelas
    public bool $showAddModal = false;
    public ?int $newStudentId = null;
    public string $newCategory = 'Kedisiplinan';
    public string $newDescription = '';
    public int $newPointChange = 5; // default +5

    public function mount(): void
    {
        $user = Auth::user();
        $myClass = Classroom::where('wali_kelas_id', $user->id)->first();
        if ($myClass) {
            $this->selectedClassroomId = $myClass->id;
        } else {
            $firstClass = Classroom::orderBy('name')->first();
            $this->selectedClassroomId = $firstClass?->id;
        }
    }

    public function openDetailModal(int $studentId): void
    {
        $this->viewStudentId = $studentId;
    }

    public function closeDetailModal(): void
    {
        $this->viewStudentId = null;
    }

    public function openAddModal(?int $studentId = null): void
    {
        $this->newStudentId = $studentId;
        $this->newCategory = 'Kedisiplinan';
        $this->newDescription = '';
        $this->newPointChange = 5;
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->newStudentId = null;
    }

    public function saveCharacterLog(): void
    {
        if (!$this->newStudentId || empty($this->newDescription)) {
            Notification::make()->title('Harap lengkapi siswa dan uraian penilaian')->danger()->send();
            return;
        }

        $student = Student::find($this->newStudentId);
        if (!$student) return;

        CharacterScoreLog::create([
            'student_id'   => $this->newStudentId,
            'source'       => 'guru_kelas',
            'category'     => $this->newCategory,
            'description'  => $this->newDescription,
            'point_change' => $this->newPointChange,
            'tanggal'      => now()->toDateString(),
            'institution_id' => $student->institution_id ?? Auth::user()->institution_id,
        ]);

        // Update score agregat siswa
        $student->increment('character_score', $this->newPointChange);

        $this->closeAddModal();

        Notification::make()
            ->title('✅ Penilaian Karakter Berhasil Dicatat')
            ->body("Poin karakter untuk {$student->name} berhasil diperbarui.")
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $user = Auth::user();

        $availableClasses = $user->hasAnyRole(['kepala_sekolah', 'superadmin'])
            ? Classroom::orderBy('name')->get()
            : Classroom::where('wali_kelas_id', $user->id)->get();

        if ($availableClasses->isEmpty()) {
            $availableClasses = Classroom::orderBy('name')->get();
        }

        $selectedClass = Classroom::find($this->selectedClassroomId);

        $studentsQuery = Student::with('characterScoreLogs');
        if ($this->selectedClassroomId) {
            $studentsQuery->where('classroom_id', $this->selectedClassroomId);
        }
        $students = $studentsQuery->orderBy('name')->get();

        $report = [];
        foreach ($students as $student) {
            $guruKelasLogs = $student->characterScoreLogs->where('source', 'guru_kelas');
            $guruPiketLogs = $student->characterScoreLogs->where('source', 'guru_piket');
            $orangTuaLogs  = $student->characterScoreLogs->where('source', 'orang_tua');

            $guruKelas = $guruKelasLogs->sum('point_change');
            $guruPiket = $guruPiketLogs->sum('point_change');
            $orangTua  = $orangTuaLogs->sum('point_change');
            $totalScore = $student->character_score ?? 100;

            $predikat = match(true) {
                $totalScore >= 90 => ['label' => 'Sangat Baik (A)', 'color' => 'success'],
                $totalScore >= 75 => ['label' => 'Baik (B)', 'color' => 'info'],
                $totalScore >= 60 => ['label' => 'Cukup (C)', 'color' => 'warning'],
                default           => ['label' => 'Perlu Pembinaan (D)', 'color' => 'danger'],
            };

            $report[] = [
                'id'         => $student->id,
                'nis'        => $student->nis,
                'name'       => $student->name,
                'total'      => $totalScore,
                'predikat'   => $predikat,
                'guru_kelas' => $guruKelas,
                'guru_piket' => $guruPiket,
                'orang_tua'  => $orangTua,
                'logs_count' => $student->characterScoreLogs->count(),
            ];
        }

        $selectedStudentForDetail = $this->viewStudentId ? Student::with(['characterScoreLogs' => function($q) {
            $q->orderByDesc('tanggal')->orderByDesc('id');
        }])->find($this->viewStudentId) : null;

        return [
            'availableClasses'         => $availableClasses,
            'selectedClass'            => $selectedClass,
            'report'                   => collect($report)->sortByDesc('total')->values()->all(),
            'selectedStudentForDetail' => $selectedStudentForDetail,
            'classStudents'            => $students,
        ];
    }
}
