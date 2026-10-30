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
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user || $user->isQuranTeacher()) {
            return false;
        }

        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user || $user->isQuranTeacher()) {
            return false;
        }

        return $user->hasAnyRole([
            'waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'wali_kelas', 'guru_kelas'
        ]);
    }

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.staff.pages.character-score-report';
    protected static ?string $title = 'Rekapitulasi Skor Karakter Siswa';
    protected static ?string $navigationLabel = 'Rekapitulasi Skor Karakter Siswa';
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

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $isRestrictedWaliKelas = $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']);

        if ($isRestrictedWaliKelas) {
            $myClass = Classroom::reguler()->where('wali_kelas_id', $user->id)->first()
                ?? Classroom::where('wali_kelas_id', $user->id)->first();
            $this->selectedClassroomId = $myClass?->id;
        } else {
            // Waka Kesiswaan, Kepala Sekolah, Superadmin: pilih kelas pertama dari seluruh kelas
            $query = Classroom::reguler()->orderBy('name');
            if (!$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
                $query->where('institution_id', $user->institution_id);
            }
            $firstClass = $query->first() ?? Classroom::orderBy('name')->first();
            $this->selectedClassroomId = $firstClass?->id;
        }
    }

    public function openDetailModal(int $studentId): void
    {
        $user = Auth::user();
        $isRestrictedWaliKelas = $user && $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']);

        if ($isRestrictedWaliKelas) {
            $myClassIds = Classroom::where('wali_kelas_id', $user->id)->pluck('id')->all();
            $student = Student::find($studentId);
            if (!$student || !in_array($student->classroom_id, $myClassIds)) {
                Notification::make()->title('Akses ditolak: Anda hanya dapat melihat rincian siswa pada kelas binaan Anda.')->danger()->send();
                return;
            }
        }

        $this->viewStudentId = $studentId;
    }

    public function closeDetailModal(): void
    {
        $this->viewStudentId = null;
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $isRestrictedWaliKelas = $user && $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']);

        if ($isRestrictedWaliKelas) {
            // Wali kelas HANYA melihat kelas yang dibinanya
            $availableClasses = Classroom::reguler()->where('wali_kelas_id', $user->id)->orderBy('name')->get();
            if ($availableClasses->isEmpty()) {
                $availableClasses = Classroom::where('wali_kelas_id', $user->id)->orderBy('name')->get();
            }

            $myClassIds = $availableClasses->pluck('id')->all();
            if (empty($myClassIds)) {
                $this->selectedClassroomId = null;
            } elseif (!in_array($this->selectedClassroomId, $myClassIds)) {
                $this->selectedClassroomId = $myClassIds[0];
            }
        } else {
            // Waka Kesiswaan, Kepala Sekolah, Superadmin melihat SELURUH KELAS
            $query = Classroom::reguler()->orderBy('name');
            if ($user && !$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
                $query->where('institution_id', $user->institution_id);
            }
            $availableClasses = $query->get();
            if ($availableClasses->isEmpty()) {
                $availableClasses = Classroom::orderBy('name')->get();
            }

            if ($this->selectedClassroomId && !$availableClasses->contains('id', $this->selectedClassroomId)) {
                $this->selectedClassroomId = $availableClasses->first()?->id;
            } elseif (!$this->selectedClassroomId) {
                $this->selectedClassroomId = $availableClasses->first()?->id;
            }
        }

        $selectedClass = $this->selectedClassroomId ? Classroom::find($this->selectedClassroomId) : null;

        $studentsQuery = Student::with('characterScoreLogs');
        if ($isRestrictedWaliKelas) {
            $myClassIds = $availableClasses->pluck('id')->all();
            if (empty($myClassIds)) {
                $studentsQuery->whereRaw('1 = 0');
            } else {
                $studentsQuery->where('classroom_id', $this->selectedClassroomId);
            }
        } else {
            if ($this->selectedClassroomId) {
                $studentsQuery->where('classroom_id', $this->selectedClassroomId);
            }
        }

        $students = $studentsQuery->orderBy('name')->get();

        $report = [];
        foreach ($students as $student) {
            $guruMapelLogs = $student->characterScoreLogs->whereIn('source', ['guru_mapel', 'guru_kelas']);
            $guruPiketLogs = $student->characterScoreLogs->where('source', 'guru_piket');
            $orangTuaLogs  = $student->characterScoreLogs->where('source', 'orang_tua');

            $guruMapel = $guruMapelLogs->sum('point_change');
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
                'guru_mapel' => $guruMapel,
                'guru_piket' => $guruPiket,
                'orang_tua'  => $orangTua,
                'logs_count' => $student->characterScoreLogs->count(),
            ];
        }

        $selectedStudentForDetail = $this->viewStudentId ? Student::with(['characterScoreLogs' => function($q) {
            $q->orderByDesc('tanggal')->orderByDesc('id');
        }])->find($this->viewStudentId) : null;

        $reportCollection = collect($report);
        $avgScore = $reportCollection->count() > 0 ? round($reportCollection->avg('total'), 1) : 0;
        $countSangatBaik = $reportCollection->where('total', '>=', 90)->count();
        $countPerluBina = $reportCollection->where('total', '<', 60)->count();

        return [
            'isRestrictedWaliKelas'    => $isRestrictedWaliKelas,
            'availableClasses'         => $availableClasses,
            'selectedClass'            => $selectedClass,
            'report'                   => $reportCollection->sortByDesc('total')->values()->all(),
            'selectedStudentForDetail' => $selectedStudentForDetail,
            'classStudents'            => $students,
            'avgScore'                 => $avgScore,
            'countSangatBaik'          => $countSangatBaik,
            'countPerluBina'           => $countPerluBina,
        ];
    }
}
