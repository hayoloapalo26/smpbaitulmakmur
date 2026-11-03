<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class RekapNilaiKelas extends Page
{
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->isQuranTeacher() && !$user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return false;
        }
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        if ($user->isQuranTeacher() && !$user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return false;
        }
        return $user->hasAnyRole(['wali_kelas', 'guru_kelas', 'koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationGroup = 'Fitur Wali Kelas';
    protected static ?string $navigationLabel = 'Rekap & Input Nilai Kelas';
    protected static ?string $title = 'Rekapitulasi & Input Nilai Mapel & Tahfidz Kelas';
    protected static string $view = 'filament.staff.pages.rekap-nilai-kelas';
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        $user = Auth::user();
        if ($user?->hasRole('koordinator_madin')) {
            return 'Madrasah Diniyah (Madin)';
        }
        if ($user?->hasRole('kepala_sekolah')) {
            return 'Perencanaan Administrasi Pembelajaran';
        }
        if ($user && $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kurikulum', 'superadmin', 'kepala_sekolah'])) {
            return 'Fitur Wali Kelas';
        }
        return 'Akademik & Kurikulum';
    }

    public static function getNavigationLabel(): string
    {
        $user = Auth::user();
        if ($user?->hasRole('koordinator_madin')) {
            return 'Rekapitulasi Nilai Al-Qur\'an';
        }
        if ($user?->hasRole('kepala_sekolah')) {
            return 'Rekap & Input Nilai';
        }
        return 'Rekap & Input Nilai Kelas';
    }

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationSort(): ?int
    {
        $user = Auth::user();
        if ($user?->hasRole('koordinator_madin')) {
            return 2;
        }
        if ($user?->hasRole('kepala_sekolah')) {
            return 3;
        }
        return 2;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('rumah_nilai_mapel')
                ->label('🏠 Rumah Input Nilai Per Mapel')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->url(fn () => url('/staff/input-nilai-mapel'))
                ->visible(fn () => Auth::user()->hasAnyRole(['guru_mapel', 'guru_kelas', 'wali_kelas', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])),

            \Filament\Actions\Action::make('kelola_nilai_mapel')
                ->label('Daftar Nilai Siswa')
                ->icon('heroicon-o-academic-cap')
                ->color('primary')
                ->url(fn () => \App\Filament\Staff\Resources\GradeResource::getUrl())
                ->visible(fn () => Auth::user()->hasAnyRole(['guru_mapel', 'guru_kelas', 'wali_kelas', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])),
        ];
    }

    public ?int $selectedClassroomId = null;
    public ?int $selectedTeacherId = null;
    public ?int $selectedAcademicYearId = null;
    public string $selectedSemester = 'Ganjil';
    public string $activeTab = 'mapel'; // 'mapel', 'tahfidz', 'input_ujian'

    // Form input nilai ujian
    public ?int $inputSubjectId = null;
    public array $examScores = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (request()->has('tab')) {
            $this->activeTab = request('tab');
        } elseif ($user?->hasRole('koordinator_madin')) {
            $this->activeTab = 'tahfidz';
        }

        if (request()->has('teacher_id')) {
            $this->selectedTeacherId = (int) request('teacher_id');
        }

        // Cari kelas yang dibina wali kelas ini (khusus kelas akademik)
        if ($user && $user->hasRole('wali_kelas')) {
            $myClass = Classroom::reguler()->where('wali_kelas_id', $user->id)->first();
            if ($myClass) {
                $this->selectedClassroomId = $myClass->id;
            }
        }

        if (!$this->selectedClassroomId && !$user?->hasRole('koordinator_madin')) {
            $firstClass = Classroom::reguler()->orderBy('name')->first() ?? Classroom::orderBy('name')->first();
            $this->selectedClassroomId = $firstClass?->id;
        }

        // Tahun ajaran aktif
        $currentYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::latest()->first();
        $this->selectedAcademicYearId = $currentYear?->id;

        // Default mapel pertama untuk input ujian
        $firstSubject = Subject::orderBy('name')->first();
        $this->inputSubjectId = $firstSubject?->id;

        $this->loadExamScores();
    }

    public function updatedSelectedClassroomId(): void
    {
        $this->loadExamScores();
    }

    public function updatedSelectedTeacherId(): void
    {
        $this->activeTab = 'tahfidz';
    }

    public function updatedSelectedAcademicYearId(): void
    {
        $this->loadExamScores();
    }

    public function updatedSelectedSemester(): void
    {
        $this->loadExamScores();
    }

    public function updatedInputSubjectId(): void
    {
        $this->loadExamScores();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        if ($tab === 'input_ujian') {
            $this->loadExamScores();
        }
    }

    public function loadExamScores(): void
    {
        if (!$this->selectedClassroomId || !$this->inputSubjectId || !$this->selectedAcademicYearId) {
            return;
        }

        $selectedClass = Classroom::find($this->selectedClassroomId);
        $students = $selectedClass ? $selectedClass->getRealStudents() : Student::where('classroom_id', $this->selectedClassroomId)->orderBy('name')->get();
        $this->examScores = [];

        foreach ($students as $student) {
            $grade = Grade::where([
                'student_id'       => $student->id,
                'subject_id'       => $this->inputSubjectId,
                'academic_year_id' => $this->selectedAcademicYearId,
                'semester'         => $this->selectedSemester,
                'type'             => 'ujian',
            ])->first();

            $this->examScores[$student->id] = $grade ? $grade->score : '';
        }
    }

    public function saveExamScores(): void
    {
        if (!$this->selectedClassroomId || !$this->inputSubjectId || !$this->selectedAcademicYearId) {
            Notification::make()->title('Harap pilih Kelas, Mapel, dan Tahun Ajaran')->danger()->send();
            return;
        }

        $count = 0;
        foreach ($this->examScores as $studentId => $score) {
            if ($score !== '' && $score !== null) {
                Grade::updateOrCreate(
                    [
                        'student_id'       => $studentId,
                        'subject_id'       => $this->inputSubjectId,
                        'academic_year_id' => $this->selectedAcademicYearId,
                        'semester'         => $this->selectedSemester,
                        'type'             => 'ujian',
                    ],
                    [
                        'score' => (float) $score,
                    ]
                );
                $count++;
            }
        }

        Notification::make()
            ->title("✅ Berhasil menyimpan {$count} nilai ujian siswa")
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $instId = $user?->institution_id;

        // 1. Daftar kelas yang dapat dipilih
        $isFullAccess = $user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']);
        $availableClasses = $isFullAccess
            ? Classroom::when($instId && !$user->hasRole('superadmin'), fn ($q) => $q->where('institution_id', $instId))->orderBy('name')->get()
            : Classroom::reguler()->where('wali_kelas_id', $user->id)->get();

        if ($availableClasses->isEmpty()) {
            $availableClasses = Classroom::orderBy('name')->get();
        }

        $academicYears = AcademicYear::orderByDesc('id')->get();
        $subjects = Subject::orderBy('name')->get();

        // 2. Daftar seluruh Guru Al-Qur'an untuk filter Koordinator Madin / Kurikulum
        $quranRoleNames = array_merge(User::QURAN_TEACHER_ROLES, ['guru_yanbua', 'guru_tahsin', 'guru_tahfidz']);
        $quranTeachers = User::where(function ($uq) use ($quranRoleNames) {
                $uq->whereHas('roles', fn ($q) => $q->whereIn('name', $quranRoleNames))
                   ->orWhereHas('santriQuran')
                   ->orWhereHas('quranClassesAsPengampu')
                   ->orWhereHas('quranAchievements')
                   ->orWhereHas('readingQualityScores');
            })
            ->when($instId && !$user->hasRole('superadmin'), fn ($q) => $q->where('institution_id', $instId))
            ->orderBy('name')
            ->get();

        // 3. Ambil data siswa
        $selectedClass = $this->selectedClassroomId ? Classroom::find($this->selectedClassroomId) : null;
        
        if ($selectedClass) {
            $studentsQuery = $selectedClass->getRealStudents();
            if ($this->selectedTeacherId) {
                $studentsQuery = $studentsQuery->filter(function ($s) {
                    return $s->guru_quran_id == $this->selectedTeacherId;
                });
            }
            $students = $studentsQuery;
        } else {
            $stQ = Student::with(['classroom', 'guruQuran']);
            if ($instId && !$user->hasRole('superadmin')) {
                $stQ->where('institution_id', $instId);
            }
            if ($this->selectedTeacherId) {
                $stQ->where('guru_quran_id', $this->selectedTeacherId);
            }
            $students = $stQ->orderBy('name')->get();
        }

        // 1. REKAP NILAI MAPEL
        $mapelReport = [];
        if ($students->isNotEmpty() && $this->selectedAcademicYearId) {
            foreach ($students as $student) {
                $grades = Grade::where('student_id', $student->id)
                    ->where('academic_year_id', $this->selectedAcademicYearId)
                    ->where('semester', $this->selectedSemester)
                    ->get();

                $subjectScores = [];
                $totalScoreSum = 0;
                $subjectCount = 0;

                foreach ($subjects as $subject) {
                    $subjectGrades = $grades->where('subject_id', $subject->id);
                    $harian = $subjectGrades->whereIn('type', ['pengetahuan', 'keterampilan'])->avg('score');
                    $ujian = $subjectGrades->where('type', 'ujian')->first()?->score;

                    $finalScore = null;
                    if ($harian !== null && $ujian !== null) {
                        $finalScore = round(($harian * 0.6) + ($ujian * 0.4), 1);
                    } elseif ($ujian !== null) {
                        $finalScore = $ujian;
                    } elseif ($harian !== null) {
                        $finalScore = round($harian, 1);
                    }

                    if ($finalScore !== null) {
                        $totalScoreSum += $finalScore;
                        $subjectCount++;
                    }

                    $subjectScores[$subject->id] = [
                        'harian' => $harian !== null ? round($harian, 1) : null,
                        'ujian'  => $ujian !== null ? round($ujian, 1) : null,
                        'final'  => $finalScore,
                    ];
                }

                $overallAverage = $subjectCount > 0 ? round($totalScoreSum / $subjectCount, 1) : 0;

                $mapelReport[] = [
                    'student'         => $student,
                    'scores'          => $subjectScores,
                    'overall_average' => $overallAverage,
                ];
            }

            // Urutkan berdasarkan rata-rata tertinggi (ranking kelas)
            $mapelReport = collect($mapelReport)->sortByDesc('overall_average')->values()->all();
        }

        // 2. REKAP NILAI TAHFIDZ & TAHSIN
        $tahfidzReport = [];
        if ($students->isNotEmpty()) {
            foreach ($students as $student) {
                $achievements = QuranAchievement::where('student_id', $student->id)
                    ->with('teacher')
                    ->orderByDesc('tanggal')
                    ->get();

                $totalZiyadah = $achievements->filter(function ($a) {
                    return is_array($a->type) ? in_array('ziyadah', $a->type) : $a->type === 'ziyadah';
                })->count();

                $totalMurajaah = $achievements->filter(function ($a) {
                    return is_array($a->type) ? in_array('murajaah', $a->type) : $a->type === 'murajaah';
                })->count();

                $latestAchievement = $achievements->first();
                $teacherName = $latestAchievement?->teacher?->name ?? $student->guruQuran?->name ?? '-';

                $qualityScore = ReadingQualityScore::where('student_id', $student->id)
                    ->orderByDesc('tanggal')
                    ->first();

                $tahfidzReport[] = [
                    'student'           => $student,
                    'classroom_name'    => $student->classroom?->name ?? '-',
                    'teacher_name'      => $teacherName,
                    'total_ziyadah'     => $totalZiyadah,
                    'total_murajaah'    => $totalMurajaah,
                    'last_juz'          => $latestAchievement?->juz ?? '-',
                    'last_surah'        => $latestAchievement?->surah ?? '-',
                    'last_ayah'         => $latestAchievement?->ayah ?? '-',
                    'last_date'         => $latestAchievement?->tanggal ?? '-',
                    'catatan'           => $latestAchievement?->catatan ?? '-',
                    'quality_scale'     => $qualityScore?->scale_score ?? null,
                    'quality_program'   => $qualityScore?->program ?? null,
                    'quality_predikat'  => $qualityScore?->predikat ?? null,
                ];
            }
        }

        return [
            'availableClasses' => $availableClasses,
            'selectedClass'    => $selectedClass,
            'academicYears'    => $academicYears,
            'subjects'         => $subjects,
            'students'         => $students,
            'mapelReport'      => $mapelReport,
            'tahfidzReport'    => $tahfidzReport,
            'quranTeachers'    => $quranTeachers,
            'isFullAccess'     => $isFullAccess,
        ];
    }
}
