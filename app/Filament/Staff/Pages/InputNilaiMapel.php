<?php

namespace App\Filament\Staff\Pages;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class InputNilaiMapel extends Page
{
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // Guru Al-Qur'an TIDAK PERNAH melihat Input Nilai Per Mapel umum di sidebar
        if ($user->isQuranTeacher()) {
            return false;
        }

        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // Guru Al-Qur'an memiliki sistem penilaian khusus (Tahfidz & Mutu Bacaan) tanpa CP, TP, dan ATP
        if ($user->isQuranTeacher()) {
            return false;
        }

        return $user->hasAnyRole([
            'guru_mapel',
            'guru_kelas',
            'waka_kurikulum',
            'kepala_sekolah',
            'superadmin',
        ]);
    }

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationGroup = 'Akademik & Kurikulum';
    protected static ?string $navigationLabel = 'Input Nilai Per Mapel';
    protected static ?string $title = 'Rumah Nilai - Input Nilai Per Mata Pelajaran';
    protected static string $view = 'filament.staff.pages.input-nilai-mapel';
    protected static ?int $navigationSort = 4;

    public ?int $selectedSubjectId = null;
    public ?int $selectedClassroomId = null;
    public ?int $selectedAcademicYearId = null;
    public string $selectedSemester = 'Ganjil';
    public array $scores = [];
    public string $search = '';

    // Quick fill helper
    public ?float $quickScorePengetahuan = null;
    public ?float $quickScoreKeterampilan = null;
    public ?float $quickScoreUjian = null;

    protected $queryString = [
        'selectedSubjectId' => ['except' => null, 'as' => 'subject_id'],
        'selectedClassroomId' => ['except' => null, 'as' => 'classroom_id'],
        'selectedSemester' => ['except' => 'Ganjil', 'as' => 'semester'],
    ];

    public function mount(?int $subject_id = null, ?int $classroom_id = null): void
    {
        $user = Auth::user();

        // 1. Tahun ajaran aktif
        $activeYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::latest()->first();
        $this->selectedAcademicYearId = $activeYear?->id;

        // Inisialisasi dari parameter / query string jika ada
        $paramSubjectId = $subject_id ?? request()->query('subject_id') ?? $this->selectedSubjectId;
        $paramClassroomId = $classroom_id ?? request()->query('classroom_id') ?? $this->selectedClassroomId;

        // 2. Tentukan mapel awal
        $accessibleSubjects = $this->getAccessibleSubjects();
        if ($paramSubjectId && $accessibleSubjects->contains('id', (int)$paramSubjectId)) {
            $this->selectedSubjectId = (int)$paramSubjectId;
        } else {
            $this->selectedSubjectId = $accessibleSubjects->first()?->id;
        }

        // 3. Tentukan kelas awal
        $accessibleClasses = $this->getAccessibleClassrooms();
        if ($paramClassroomId && $accessibleClasses->contains('id', (int)$paramClassroomId)) {
            $this->selectedClassroomId = (int)$paramClassroomId;
        } else {
            $this->selectedClassroomId = $accessibleClasses->first()?->id;
        }

        $this->loadStudentScores();
    }

    public function updatedSelectedSubjectId(): void
    {
        // Sesuaikan pilihan kelas untuk mapel baru
        $accessibleClasses = $this->getAccessibleClassrooms();
        if (!$accessibleClasses->contains('id', $this->selectedClassroomId)) {
            $this->selectedClassroomId = $accessibleClasses->first()?->id;
        }
        $this->loadStudentScores();
    }

    public function updatedSelectedClassroomId(): void
    {
        $this->loadStudentScores();
    }

    public function updatedSelectedAcademicYearId(): void
    {
        $this->loadStudentScores();
    }

    public function updatedSelectedSemester(): void
    {
        $this->loadStudentScores();
    }

    public function selectSubject(int $subjectId): void
    {
        $this->selectedSubjectId = $subjectId;
        $this->updatedSelectedSubjectId();
    }

    public function selectClassroom(int $classroomId): void
    {
        $this->selectedClassroomId = $classroomId;
        $this->loadStudentScores();
    }

    public function loadStudentScores(): void
    {
        if (!$this->selectedSubjectId || !$this->selectedClassroomId || !$this->selectedAcademicYearId) {
            $this->scores = [];
            return;
        }

        $students = Student::with(['guruQuran', 'classroom'])
            ->where('classroom_id', $this->selectedClassroomId)
            ->orderBy('name')
            ->get();

        $existingGrades = Grade::where([
            'subject_id'       => $this->selectedSubjectId,
            'academic_year_id' => $this->selectedAcademicYearId,
            'semester'         => $this->selectedSemester,
        ])
        ->whereIn('student_id', $students->pluck('id'))
        ->get();

        $this->scores = [];
        foreach ($students as $student) {
            $studentGrades = $existingGrades->where('student_id', $student->id);
            $p = $studentGrades->firstWhere('type', 'pengetahuan')?->score;
            $k = $studentGrades->firstWhere('type', 'keterampilan')?->score;
            $u = $studentGrades->firstWhere('type', 'ujian')?->score;

            $this->scores[$student->id] = [
                'pengetahuan'  => $p !== null ? (string)$p : '',
                'keterampilan' => $k !== null ? (string)$k : '',
                'ujian'        => $u !== null ? (string)$u : '',
            ];
        }
    }

    public function saveScores(): void
    {
        if (!$this->selectedSubjectId || !$this->selectedClassroomId || !$this->selectedAcademicYearId) {
            Notification::make()
                ->title('Harap pilih Mata Pelajaran, Kelas, dan Periode terlebih dahulu')
                ->danger()
                ->send();
            return;
        }

        $subject = Subject::find($this->selectedSubjectId);
        $classroom = Classroom::find($this->selectedClassroomId);

        $savedCount = 0;
        foreach ($this->scores as $studentId => $row) {
            $types = [
                'pengetahuan'  => $row['pengetahuan'] ?? '',
                'keterampilan' => $row['keterampilan'] ?? '',
                'ujian'        => $row['ujian'] ?? '',
            ];

            foreach ($types as $type => $value) {
                if ($value !== '' && $value !== null && is_numeric($value)) {
                    $scoreVal = max(0, min(100, (float)$value));

                    Grade::updateOrCreate(
                        [
                            'student_id'       => $studentId,
                            'subject_id'       => $this->selectedSubjectId,
                            'academic_year_id' => $this->selectedAcademicYearId,
                            'semester'         => $this->selectedSemester,
                            'type'             => $type,
                        ],
                        [
                            'score'        => $scoreVal,
                            'classroom_id' => $this->selectedClassroomId,
                            'teacher_id'   => Auth::id(),
                        ]
                    );
                    $savedCount++;
                } elseif ($value === '') {
                    // Hapus jika dikosongkan guru
                    Grade::where([
                        'student_id'       => $studentId,
                        'subject_id'       => $this->selectedSubjectId,
                        'academic_year_id' => $this->selectedAcademicYearId,
                        'semester'         => $this->selectedSemester,
                        'type'             => $type,
                    ])->delete();
                }
            }
        }

        Notification::make()
            ->title("✅ Nilai {$subject?->name} Kelas {$classroom?->name} Berhasil Disimpan!")
            ->body("Sebanyak data nilai untuk siswa berhasil diperbarui pada database.")
            ->success()
            ->send();

        $this->loadStudentScores();
    }

    public function applyQuickFill(string $type): void
    {
        $val = match ($type) {
            'pengetahuan'  => $this->quickScorePengetahuan,
            'keterampilan' => $this->quickScoreKeterampilan,
            'ujian'        => $this->quickScoreUjian,
            default        => null,
        };

        if ($val === null || $val === '') {
            Notification::make()->title('Masukkan nilai template terlebih dahulu')->warning()->send();
            return;
        }

        $val = max(0, min(100, (float)$val));
        foreach ($this->scores as $studentId => &$row) {
            if ($row[$type] === '' || $row[$type] === null) {
                $row[$type] = (string)$val;
            }
        }

        Notification::make()
            ->title("Nilai {$type} template ({$val}) diterapkan pada siswa yang masih kosong")
            ->info()
            ->send();
    }

    public function getAccessibleSubjects(): Collection
    {
        $user = Auth::user();
        if (!$user) {
            return collect();
        }

        // Jika Kurikulum / Kepsek / Admin / Koordinator Madin: melihat seluruh mapel akademik (tanpa mapel Al-Qur'an)
        if ($user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return Subject::akademik()->orderBy('name')->get();
        }

        // Jika Guru: ambil mapel akademik yang ditugaskan ke guru ini
        $assignedSubjectIds = TeacherSubjectClass::where('teacher_id', $user->id)
            ->pluck('subject_id')
            ->unique()
            ->toArray();

        $subjects = Subject::akademik()->whereIn('id', $assignedSubjectIds)->orderBy('name')->get();

        // Fallback jika belum disetting oleh kurikulum: selalu hanya memuat mapel akademik murni
        if ($subjects->isEmpty()) {
            return Subject::akademik()->orderBy('name')->get();
        }

        return $subjects;
    }

    public function getAccessibleClassrooms(): Collection
    {
        $user = Auth::user();
        if (!$user) {
            return collect();
        }

        // Jika Kurikulum / Kepsek / Admin / Koordinator Madin: melihat seluruh kelas
        if ($user->hasAnyRole(['koordinator_madin', 'waka_kurikulum', 'kepala_sekolah', 'superadmin'])) {
            return Classroom::orderBy('name')->get();
        }

        // Jika Guru mengajar mapel tertentu: filter kelas yang diampu untuk mapel tersebut
        if ($this->selectedSubjectId) {
            $assignedClassroomIds = TeacherSubjectClass::where('teacher_id', $user->id)
                ->where('subject_id', $this->selectedSubjectId)
                ->pluck('classroom_id')
                ->unique()
                ->toArray();

            if (!empty($assignedClassroomIds)) {
                return Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
            }
        }

        // Jika Wali Kelas
        $myClass = Classroom::reguler()->where('wali_kelas_id', $user->id)->get();
        if ($myClass->isNotEmpty()) {
            return $myClass;
        }

        return Classroom::reguler()->orderBy('name')->get();
    }

    public function calculateFinalScore($p, $k, $u): ?float
    {
        $p = ($p !== '' && $p !== null) ? (float)$p : null;
        $k = ($k !== '' && $k !== null) ? (float)$k : null;
        $u = ($u !== '' && $u !== null) ? (float)$u : null;

        if ($p !== null && $k !== null && $u !== null) {
            return round(($p * 0.3) + ($k * 0.3) + ($u * 0.4), 1);
        } elseif ($p !== null && $u !== null) {
            return round(($p * 0.5) + ($u * 0.5), 1);
        } elseif ($p !== null && $k !== null) {
            return round(($p * 0.5) + ($k * 0.5), 1);
        } elseif ($u !== null) {
            return round($u, 1);
        } elseif ($p !== null) {
            return round($p, 1);
        } elseif ($k !== null) {
            return round($k, 1);
        }

        return null;
    }

    public function getPredicate(?float $score): array
    {
        if ($score === null) {
            return ['grade' => '-', 'label' => 'Belum Lengkap', 'color' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400', 'is_tuntas' => false];
        }

        if ($score >= 100) {
            return ['grade' => 'A+', 'label' => '100 : Excellent', 'color' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-300 dark:border-amber-700', 'is_tuntas' => true];
        } elseif ($score >= 90) {
            return ['grade' => 'A', 'label' => '90 - 99 : Sangat Baik', 'color' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700', 'is_tuntas' => true];
        } elseif ($score >= 80) {
            return ['grade' => 'B', 'label' => '80 - 89 : Baik', 'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-300 dark:border-blue-700', 'is_tuntas' => true];
        } elseif ($score >= 70) {
            return ['grade' => 'C', 'label' => '70 - 79 : Cukup Baik', 'color' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950/50 dark:text-yellow-300 border border-yellow-300 dark:border-yellow-700', 'is_tuntas' => true];
        } else {
            return ['grade' => 'D', 'label' => '< 70 : Perlu Pembinaan', 'color' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-300 dark:border-rose-700', 'is_tuntas' => false];
        }
    }

    protected function getViewData(): array
    {
        $subjects = $this->getAccessibleSubjects();
        $classrooms = $this->getAccessibleClassrooms();
        $academicYears = AcademicYear::orderByDesc('id')->get();

        $selectedSubject = Subject::find($this->selectedSubjectId);
        $selectedClassroom = Classroom::find($this->selectedClassroomId);

        // Cari siapa guru yang ditugaskan mengajar mapel ini di kelas ini
        $assignedTeacher = null;
        if ($this->selectedSubjectId && $this->selectedClassroomId) {
            $setting = TeacherSubjectClass::where([
                'subject_id'   => $this->selectedSubjectId,
                'classroom_id' => $this->selectedClassroomId,
            ])->with('teacher')->first();

            $assignedTeacher = $setting?->teacher;
        }

        // Ambil daftar siswa murni per kelas / rombel reguler
        $studentsQuery = Student::with(['guruQuran', 'classroom']);
        if ($this->selectedClassroomId) {
            $studentsQuery->where('classroom_id', $this->selectedClassroomId);
        } else {
            $studentsQuery->whereRaw('1=0');
        }

        if (!empty($this->search)) {
            $studentsQuery->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('nis', 'like', '%' . $this->search . '%');
            });
        }

        $students = $studentsQuery->orderBy('name')->get();

        // Hitung statistik
        $totalStudents = $students->count();
        $completeCount = 0;
        $totalFinalScore = 0;
        $scoredCount = 0;
        $tuntasCount = 0;

        foreach ($students as $st) {
            $row = $this->scores[$st->id] ?? [];
            $final = $this->calculateFinalScore($row['pengetahuan'] ?? '', $row['keterampilan'] ?? '', $row['ujian'] ?? '');
            if ($final !== null) {
                $totalFinalScore += $final;
                $scoredCount++;
                if ($final >= 70) {
                    $tuntasCount++;
                }
            }

            if (!empty($row['pengetahuan']) && !empty($row['keterampilan']) && !empty($row['ujian'])) {
                $completeCount++;
            }
        }

        $averageScore = $scoredCount > 0 ? round($totalFinalScore / $scoredCount, 1) : 0;
        $tuntasPercent = $scoredCount > 0 ? round(($tuntasCount / $scoredCount) * 100) : 0;

        return [
            'subjects'          => $subjects,
            'classrooms'        => $classrooms,
            'academicYears'     => $academicYears,
            'selectedSubject'   => $selectedSubject,
            'selectedClassroom' => $selectedClassroom,
            'assignedTeacher'   => $assignedTeacher,
            'students'          => $students,
            'totalStudents'     => $totalStudents,
            'completeCount'     => $completeCount,
            'averageScore'      => $averageScore,
            'tuntasPercent'     => $tuntasPercent,
        ];
    }
}
