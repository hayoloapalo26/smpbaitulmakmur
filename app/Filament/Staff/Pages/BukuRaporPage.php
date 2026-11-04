<?php

namespace App\Filament\Staff\Pages;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\RaporCalculationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class BukuRaporPage extends Page
{
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // Guru Al-Qur'an murni memiliki portal dan sistem penilaian Al-Qur'an tersendiri
        if ($user->isQuranTeacher() && !$user->hasAnyRole(['wali_kelas', 'guru_kelas', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return false;
        }

        return $user->hasAnyRole([
            'wali_kelas',
            'guru_kelas',
            'waka_kurikulum',
            'kepala_sekolah',
            'superadmin',
        ]);
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Buku Rapor & Leger';
    protected static ?string $title = 'Buku Rapor Siswa & Leger Nilai';
    protected static string $view = 'filament.staff.pages.buku-rapor';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        $user = Auth::user();
        if ($user && $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah'])) {
            return 'Fitur Wali Kelas';
        }
        return 'Akademik & Kurikulum';
    }

    public ?int $selectedClassroomId = null;
    public ?int $selectedAcademicYearId = null;
    public string $selectedSemester = 'Ganjil';
    public string $activeTab = 'rapor'; // 'rapor' | 'leger'

    // Modal preview rapor
    public ?int $previewStudentId = null;
    public ?array $previewRaporData = null;

    public function mount(): void
    {
        $user = Auth::user();

        // 1. Inisialisasi Kelas
        if ($user && $user->hasRole('wali_kelas')) {
            $myClass = Classroom::reguler()->where('wali_kelas_id', $user->id)->first();
            if ($myClass) {
                $this->selectedClassroomId = $myClass->id;
            }
        }

        if (!$this->selectedClassroomId) {
            $firstClass = Classroom::reguler()
                ->when($user?->institution_id && !$user->hasRole('superadmin'), fn ($q) => $q->where('institution_id', $user->institution_id))
                ->orderBy('name')
                ->first();
            $this->selectedClassroomId = $firstClass?->id;
        }

        // 2. Tahun ajaran aktif
        $currentYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::latest()->first();
        $this->selectedAcademicYearId = $currentYear?->id;
    }

    public function updatedSelectedClassroomId(): void
    {
        $this->closePreviewModal();
    }

    public function updatedSelectedAcademicYearId(): void
    {
        $this->closePreviewModal();
    }

    public function updatedSelectedSemester(): void
    {
        $this->closePreviewModal();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->closePreviewModal();
    }

    public function openPreviewRapor(int $studentId): void
    {
        $student = Student::with(['classroom', 'institution'])->find($studentId);
        if (!$student) {
            Notification::make()->title('Data siswa tidak ditemukan')->danger()->send();
            return;
        }

        $this->previewStudentId = $studentId;
        $this->previewRaporData = RaporCalculationService::getRaporData($student, $this->selectedAcademicYearId, $this->selectedSemester);
    }

    public function closePreviewModal(): void
    {
        $this->previewStudentId = null;
        $this->previewRaporData = null;
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $instId = $user?->institution_id;

        // Daftar kelas yang dapat diakses
        $isFullAccess = $user->hasAnyRole(['waka_kurikulum', 'kepala_sekolah', 'superadmin']);
        $availableClasses = $isFullAccess
            ? Classroom::when($instId && !$user->hasRole('superadmin'), fn ($q) => $q->where('institution_id', $instId))->orderBy('name')->get()
            : Classroom::reguler()->where('wali_kelas_id', $user->id)->get();

        if ($availableClasses->isEmpty()) {
            $availableClasses = Classroom::orderBy('name')->get();
        }

        $academicYears = AcademicYear::orderByDesc('id')->get();
        $selectedClass = $this->selectedClassroomId ? Classroom::with('waliKelas')->find($this->selectedClassroomId) : null;

        $legerData = [];
        if ($this->selectedClassroomId && $this->selectedAcademicYearId) {
            $legerData = RaporCalculationService::getClassLeger(
                $this->selectedClassroomId,
                $this->selectedAcademicYearId,
                $this->selectedSemester
            );
        }

        return [
            'availableClasses' => $availableClasses,
            'academicYears'    => $academicYears,
            'selectedClass'    => $selectedClass,
            'legerData'        => $legerData,
        ];
    }
}
