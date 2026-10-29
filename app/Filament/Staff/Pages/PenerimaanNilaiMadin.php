<?php

namespace App\Filament\Staff\Pages;

use App\Filament\Staff\Resources\QuranAchievementResource;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\QuranAchievement;
use App\Models\ReadingQualityScore;
use App\Models\Subject;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PenerimaanNilaiMadin extends Page
{
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasAnyRole([
            'koordinator_madin',
            'waka_kurikulum',
            'kepala_sekolah',
            'superadmin',
        ]) ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->hasAnyRole([
            'koordinator_madin',
            'waka_kurikulum',
            'kepala_sekolah',
            'superadmin',
        ]) ?? false;
    }

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationGroup = 'Madrasah Diniyah (Madin)';
    protected static ?string $navigationLabel = 'Penerimaan Nilai Al-Qur\'an';
    protected static ?string $title = 'Penerimaan & Verifikasi Nilai Guru Al-Qur\'an';
    protected static string $view = 'filament.staff.pages.penerimaan-nilai-madin';
    protected static ?int $navigationSort = 1;

    public static function applyInstitutionScope(\Illuminate\Database\Eloquent\Builder $query, ?int $instId, $user): \Illuminate\Database\Eloquent\Builder
    {
        if (!$instId || ($user && $user->hasRole('superadmin'))) {
            return $query;
        }

        return $query->where(function ($q) use ($instId) {
            $q->whereHas('student.classroom', fn ($c) => $c->where('institution_id', $instId))
              ->orWhereHas('student', fn ($s) => $s->where('institution_id', $instId));
        });
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        if (!$user) return null;

        $instId = $user->institution_id;

        $pendingCapaian = static::applyInstitutionScope(
            QuranAchievement::where('status_verifikasi', 'Menunggu Verifikasi'),
            $instId,
            $user
        )->count();

        $pendingKualitas = static::applyInstitutionScope(
            ReadingQualityScore::where('status_verifikasi', 'Menunggu Verifikasi'),
            $instId,
            $user
        )->count();

        $gradesBaseQuery = Grade::where('status_verifikasi', 'Menunggu Verifikasi')
            ->where(function ($q) {
                $q->whereHas('subject', function ($sq) {
                    $sq->where('name', 'like', '%qur%')
                      ->orWhere('name', 'like', '%tahfidz%')
                      ->orWhere('name', 'like', '%tahsin%')
                      ->orWhere('name', 'like', '%yanbua%')
                      ->orWhere('name', 'like', '%madin%')
                      ->orWhere('name', 'like', '%diniyah%')
                      ->orWhere('name', 'like', '%pai%');
                })->orWhereHas('teacher', fn ($t) => $t->whereHas('roles', fn ($r) => $r->whereIn('name', User::QURAN_TEACHER_ROLES)));
            });

        $pendingGrades = static::applyInstitutionScope($gradesBaseQuery, $instId, $user)->count();

        $totalPending = $pendingCapaian + $pendingKualitas + $pendingGrades;
        return $totalPending > 0 ? (string) $totalPending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // Filter properties
    public ?int $selectedTeacherId = null;
    public ?string $selectedSpecialization = null; // 'yanbua', 'tahsin', 'tahfidz_dasar', 'tahfidz_lanjutan'
    public ?int $selectedClassroomId = null;
    public ?int $selectedAcademicYearId = null;
    public string $selectedSemester = 'Ganjil';
    public string $selectedStatus = 'Semua'; // 'Semua', 'Menunggu Verifikasi', 'Diterima', 'Perlu Perbaikan'
    public string $activeTab = 'monitoring_guru'; // 'monitoring_guru', 'capaian_quran', 'kualitas_bacaan', 'nilai_mapel'
    public string $search = '';

    // Modal state for notes
    public ?int $selectedRecordId = null;
    public ?string $selectedRecordType = null;
    public string $catatanFeedback = '';
    public bool $showFeedbackModal = false;

    public function mount(): void
    {
        $currentYear = AcademicYear::where('is_active', true)->first() ?? AcademicYear::latest()->first();
        $this->selectedAcademicYearId = $currentYear?->id;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Aksi Koordinator: Terima 1 Capaian Quran
     */
    public function terimaCapaian(int $id): void
    {
        $record = QuranAchievement::find($id);
        if ($record) {
            $record->update([
                'status_verifikasi' => 'Diterima',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

            Notification::make()
                ->title('Capaian Al-Qur\'an Berhasil Diterima')
                ->body("Setoran siswa {$record->student?->name} diverifikasi.")
                ->success()
                ->send();
        }
    }

    /**
     * Aksi Koordinator: Terima 1 Skor Kualitas Bacaan
     */
    public function terimaKualitas(int $id): void
    {
        $record = ReadingQualityScore::find($id);
        if ($record) {
            $record->update([
                'status_verifikasi' => 'Diterima',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

            Notification::make()
                ->title('Skor Kualitas Bacaan Berhasil Diterima')
                ->body("Penilaian {$record->program} siswa {$record->student?->name} diverifikasi.")
                ->success()
                ->send();
        }
    }

    /**
     * Aksi Koordinator: Terima 1 Nilai Mapel
     */
    public function terimaNilai(int $id): void
    {
        $record = Grade::find($id);
        if ($record) {
            $record->update([
                'status_verifikasi' => 'Diterima',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

            Notification::make()
                ->title('Nilai Mapel Diniyah Berhasil Diterima')
                ->body("Nilai {$record->subject?->name} siswa {$record->student?->name} diverifikasi.")
                ->success()
                ->send();
        }
    }

    /**
     * Aksi Buka Modal Feedback / Catatan Revisi
     */
    public function openFeedbackModal(string $type, int $id): void
    {
        $this->selectedRecordType = $type;
        $this->selectedRecordId = $id;
        $this->catatanFeedback = '';
        $this->showFeedbackModal = true;
    }

    public function closeFeedbackModal(): void
    {
        $this->showFeedbackModal = false;
        $this->selectedRecordId = null;
        $this->selectedRecordType = null;
        $this->catatanFeedback = '';
    }

    /**
     * Simpan Catatan Feedback / Minta Perbaikan
     */
    public function simpanCatatanFeedback(): void
    {
        if (!$this->selectedRecordId || !$this->selectedRecordType) {
            return;
        }

        $model = match ($this->selectedRecordType) {
            'capaian'  => QuranAchievement::find($this->selectedRecordId),
            'kualitas' => ReadingQualityScore::find($this->selectedRecordId),
            'nilai'    => Grade::find($this->selectedRecordId),
            default    => null,
        };

        if ($model) {
            $model->update([
                'status_verifikasi' => 'Perlu Perbaikan',
                'catatan_koordinator' => $this->catatanFeedback,
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

            Notification::make()
                ->title('Catatan Perbaikan Terkirim')
                ->body('Status diubah menjadi "Perlu Perbaikan" dengan catatan untuk guru Al-Qur\'an terkait.')
                ->warning()
                ->send();
        }

        $this->closeFeedbackModal();
    }

    /**
     * Aksi Cepat Massal: Terima seluruh nilai dari Guru Al-Qur'an tertentu
     */
    public function terimaSemuaDariGuru(int $teacherId): void
    {
        $teacher = User::find($teacherId);
        $teacherName = $teacher?->name ?? 'Guru Al-Qur\'an';

        // 1. Terima Capaian Quran
        $capaianCount = QuranAchievement::where('teacher_id', $teacherId)
            ->where('status_verifikasi', '!=', 'Diterima')
            ->update([
                'status_verifikasi' => 'Diterima',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

        // 2. Terima Skor Kualitas
        $kualitasCount = ReadingQualityScore::where('teacher_id', $teacherId)
            ->where('status_verifikasi', '!=', 'Diterima')
            ->update([
                'status_verifikasi' => 'Diterima',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

        // 3. Terima Nilai Mapel
        $gradesCount = Grade::where('teacher_id', $teacherId)
            ->where('status_verifikasi', '!=', 'Diterima')
            ->update([
                'status_verifikasi' => 'Diterima',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);

        $total = $capaianCount + $kualitasCount + $gradesCount;

        if ($total > 0) {
            Notification::make()
                ->title("✅ Seluruh Nilai dari {$teacherName} Berhasil Diterima!")
                ->body("Sebanyak {$total} inputan nilai (Capaian: {$capaianCount}, Kualitas: {$kualitasCount}, Mapel: {$gradesCount}) berhasil diverifikasi.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title("Semua Nilai dari {$teacherName} Sudah Diterima")
                ->body("Tidak ada inputan baru yang berstatus menunggu verifikasi.")
                ->info()
                ->send();
        }
    }

    /**
     * Aksi Massal: Terima semua data pada tab yang sedang aktif
     */
    public function terimaSemuaPadaTabAktif(): void
    {
        $authId = Auth::id();
        $now = now();
        $instId = Auth::user()?->institution_id;

        if ($this->activeTab === 'capaian_quran') {
            $query = QuranAchievement::where('status_verifikasi', '!=', 'Diterima')
                ->when($this->selectedTeacherId, fn ($q) => $q->where('teacher_id', $this->selectedTeacherId))
                ->when($this->selectedClassroomId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('classroom_id', $this->selectedClassroomId)));

            $count = $query->update([
                'status_verifikasi' => 'Diterima',
                'verified_by' => $authId,
                'verified_at' => $now,
            ]);

            Notification::make()->title("✅ {$count} Setoran Hafalan Berhasil Diterima!")->success()->send();
        } elseif ($this->activeTab === 'kualitas_bacaan') {
            $query = ReadingQualityScore::where('status_verifikasi', '!=', 'Diterima')
                ->when($this->selectedTeacherId, fn ($q) => $q->where('teacher_id', $this->selectedTeacherId))
                ->when($this->selectedClassroomId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('classroom_id', $this->selectedClassroomId)));

            $count = $query->update([
                'status_verifikasi' => 'Diterima',
                'verified_by' => $authId,
                'verified_at' => $now,
            ]);

            Notification::make()->title("✅ {$count} Skor Kualitas Bacaan Berhasil Diterima!")->success()->send();
        } elseif ($this->activeTab === 'nilai_mapel') {
            $query = Grade::where('status_verifikasi', '!=', 'Diterima')
                ->when($this->selectedTeacherId, fn ($q) => $q->where('teacher_id', $this->selectedTeacherId))
                ->when($this->selectedClassroomId, fn ($q) => $q->where('classroom_id', $this->selectedClassroomId));

            $count = $query->update([
                'status_verifikasi' => 'Diterima',
                'verified_by' => $authId,
                'verified_at' => $now,
            ]);

            Notification::make()->title("✅ {$count} Nilai Mapel Diniyah Berhasil Diterima!")->success()->send();
        }
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $instId = $user?->institution_id;

        // 1. Seluruh Guru Al-Qur'an (Termasuk Yanbu'a, Tahsin, Tahfidz Dasar, Tahfidz Lanjutan)
        $quranTeachers = User::whereHas('roles', fn ($q) => $q->whereIn('name', User::QURAN_TEACHER_ROLES))
            ->when($instId && !$user->hasRole('superadmin'), fn ($q) => $q->where('institution_id', $instId))
            ->when($this->selectedSpecialization, function ($q) {
                $roleName = match ($this->selectedSpecialization) {
                    'yanbua' => 'guru_quran_yanbua',
                    'tahsin' => 'guru_quran_tahsin',
                    'tahfidz_dasar' => 'guru_quran_tahfidz_dasar',
                    'tahfidz_lanjutan' => 'guru_quran_tahfidz_lanjutan',
                    default => null,
                };
                if ($roleName) {
                    $q->whereHas('roles', fn ($r) => $r->where('name', $roleName));
                }
            })
            ->orderBy('name')
            ->get();

        // 2. Seluruh Kelas
        $classrooms = Classroom::when($instId && !$user->hasRole('superadmin'), fn ($q) => $q->where('institution_id', $instId))
            ->orderBy('name')
            ->get();

        // 3. Tahun Ajaran
        $academicYears = AcademicYear::orderByDesc('id')->get();

        // --- STATS OVERVIEW ---
        $stats = [
            'total_guru' => $quranTeachers->count(),
            'pending_capaian' => static::applyInstitutionScope(QuranAchievement::where('status_verifikasi', 'Menunggu Verifikasi'), $instId, $user)->count(),
            'accepted_capaian' => static::applyInstitutionScope(QuranAchievement::where('status_verifikasi', 'Diterima'), $instId, $user)->count(),
            'pending_kualitas' => static::applyInstitutionScope(ReadingQualityScore::where('status_verifikasi', 'Menunggu Verifikasi'), $instId, $user)->count(),
            'accepted_kualitas' => static::applyInstitutionScope(ReadingQualityScore::where('status_verifikasi', 'Diterima'), $instId, $user)->count(),
            'pending_nilai' => static::applyInstitutionScope(Grade::where('status_verifikasi', 'Menunggu Verifikasi')
                ->where(function ($q) {
                    $q->whereHas('subject', function ($sq) {
                        $sq->where('name', 'like', '%qur%')
                          ->orWhere('name', 'like', '%tahfidz%')
                          ->orWhere('name', 'like', '%tahsin%')
                          ->orWhere('name', 'like', '%yanbua%')
                          ->orWhere('name', 'like', '%madin%')
                          ->orWhere('name', 'like', '%diniyah%')
                          ->orWhere('name', 'like', '%pai%');
                    })->orWhereHas('teacher', fn ($t) => $t->whereHas('roles', fn ($r) => $r->whereIn('name', User::QURAN_TEACHER_ROLES)));
                }), $instId, $user)->count(),
            'accepted_nilai' => static::applyInstitutionScope(Grade::where('status_verifikasi', 'Diterima')
                ->where(function ($q) {
                    $q->whereHas('subject', function ($sq) {
                        $sq->where('name', 'like', '%qur%')
                          ->orWhere('name', 'like', '%tahfidz%')
                          ->orWhere('name', 'like', '%tahsin%')
                          ->orWhere('name', 'like', '%yanbua%')
                          ->orWhere('name', 'like', '%madin%')
                          ->orWhere('name', 'like', '%diniyah%')
                          ->orWhere('name', 'like', '%pai%');
                    })->orWhereHas('teacher', fn ($t) => $t->whereHas('roles', fn ($r) => $r->whereIn('name', User::QURAN_TEACHER_ROLES)));
                }), $instId, $user)->count(),
        ];
        $stats['total_pending'] = $stats['pending_capaian'] + $stats['pending_kualitas'] + $stats['pending_nilai'];
        $stats['total_accepted'] = $stats['accepted_capaian'] + $stats['accepted_kualitas'] + $stats['accepted_nilai'];

        // --- DATA TAB 1: CAPAIAN QURAN ---
        $capaianQuery = static::applyInstitutionScope(
            QuranAchievement::with(['student.classroom', 'teacher.roles', 'verifier']),
            $instId,
            $user
        )
            ->when($this->selectedTeacherId, fn ($q) => $q->where('teacher_id', $this->selectedTeacherId))
            ->when($this->selectedSpecialization, function ($q) {
                $roleName = match ($this->selectedSpecialization) {
                    'yanbua' => 'guru_quran_yanbua',
                    'tahsin' => 'guru_quran_tahsin',
                    'tahfidz_dasar' => 'guru_quran_tahfidz_dasar',
                    'tahfidz_lanjutan' => 'guru_quran_tahfidz_lanjutan',
                    default => null,
                };
                if ($roleName) {
                    $q->whereHas('teacher.roles', fn ($r) => $r->where('name', $roleName));
                }
            })
            ->when($this->selectedClassroomId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('classroom_id', $this->selectedClassroomId)))
            ->when($this->selectedStatus !== 'Semua', fn ($q) => $q->where('status_verifikasi', $this->selectedStatus))
            ->when($this->search, function ($q) {
                $q->whereHas('student', fn ($s) => $s->where('name', 'like', "%{$this->search}%"))
                  ->orWhere('catatan', 'like', "%{$this->search}%");
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        $capaianData = $this->activeTab === 'capaian_quran' ? $capaianQuery->paginate(20) : null;

        // --- DATA TAB 2: SKOR KUALITAS BACAAN ---
        $kualitasQuery = static::applyInstitutionScope(
            ReadingQualityScore::with(['student.classroom', 'teacher.roles', 'verifier']),
            $instId,
            $user
        )
            ->when($this->selectedTeacherId, fn ($q) => $q->where('teacher_id', $this->selectedTeacherId))
            ->when($this->selectedSpecialization, function ($q) {
                $roleName = match ($this->selectedSpecialization) {
                    'yanbua' => 'guru_quran_yanbua',
                    'tahsin' => 'guru_quran_tahsin',
                    'tahfidz_dasar' => 'guru_quran_tahfidz_dasar',
                    'tahfidz_lanjutan' => 'guru_quran_tahfidz_lanjutan',
                    default => null,
                };
                if ($roleName) {
                    $q->whereHas('teacher.roles', fn ($r) => $r->where('name', $roleName));
                }
            })
            ->when($this->selectedClassroomId, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('classroom_id', $this->selectedClassroomId)))
            ->when($this->selectedStatus !== 'Semua', fn ($q) => $q->where('status_verifikasi', $this->selectedStatus))
            ->when($this->search, function ($q) {
                $q->whereHas('student', fn ($s) => $s->where('name', 'like', "%{$this->search}%"));
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        $kualitasData = $this->activeTab === 'kualitas_bacaan' ? $kualitasQuery->paginate(20) : null;

        // --- DATA TAB 3: NILAI MAPEL MADIN ---
        $gradesBase = Grade::with(['student.classroom', 'teacher.roles', 'subject', 'verifier'])
            ->where(function ($q) {
                $q->whereHas('subject', function ($sq) {
                    $sq->where('name', 'like', '%qur%')
                      ->orWhere('name', 'like', '%tahfidz%')
                      ->orWhere('name', 'like', '%tahsin%')
                      ->orWhere('name', 'like', '%yanbua%')
                      ->orWhere('name', 'like', '%madin%')
                      ->orWhere('name', 'like', '%diniyah%')
                      ->orWhere('name', 'like', '%pai%');
                })->orWhereHas('teacher', fn ($t) => $t->whereHas('roles', fn ($r) => $r->whereIn('name', User::QURAN_TEACHER_ROLES)));
            });

        $gradesQuery = static::applyInstitutionScope($gradesBase, $instId, $user)
            ->when($this->selectedTeacherId, fn ($q) => $q->where('teacher_id', $this->selectedTeacherId))
            ->when($this->selectedSpecialization, function ($q) {
                $roleName = match ($this->selectedSpecialization) {
                    'yanbua' => 'guru_quran_yanbua',
                    'tahsin' => 'guru_quran_tahsin',
                    'tahfidz_dasar' => 'guru_quran_tahfidz_dasar',
                    'tahfidz_lanjutan' => 'guru_quran_tahfidz_lanjutan',
                    default => null,
                };
                if ($roleName) {
                    $q->whereHas('teacher.roles', fn ($r) => $r->where('name', $roleName));
                }
            })
            ->when($this->selectedClassroomId, fn ($q) => $q->where('classroom_id', $this->selectedClassroomId))
            ->when($this->selectedAcademicYearId, fn ($q) => $q->where('academic_year_id', $this->selectedAcademicYearId))
            ->when($this->selectedSemester, fn ($q) => $q->where('semester', $this->selectedSemester))
            ->when($this->selectedStatus !== 'Semua', fn ($q) => $q->where('status_verifikasi', $this->selectedStatus))
            ->when($this->search, function ($q) {
                $q->whereHas('student', fn ($s) => $s->where('name', 'like', "%{$this->search}%"))
                  ->orWhere('catatan_guru', 'like', "%{$this->search}%");
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        $gradesData = $this->activeTab === 'nilai_mapel' ? $gradesQuery->paginate(20) : null;

        // --- DATA TAB 4: MONITORING PROGRESS SELURUH GURU AL-QUR'AN ---
        $monitoringGuru = [];
        foreach ($quranTeachers as $teacher) {
            $tClasses = TeacherSubjectClass::where('teacher_id', $teacher->id)
                ->with('classroom')
                ->get()
                ->pluck('classroom.name')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $totalCapaian = QuranAchievement::where('teacher_id', $teacher->id)->count();
            $pendingCapaian = QuranAchievement::where('teacher_id', $teacher->id)->where('status_verifikasi', 'Menunggu Verifikasi')->count();

            $totalKualitas = ReadingQualityScore::where('teacher_id', $teacher->id)->count();
            $pendingKualitas = ReadingQualityScore::where('teacher_id', $teacher->id)->where('status_verifikasi', 'Menunggu Verifikasi')->count();

            $totalGrades = Grade::where('teacher_id', $teacher->id)->count();
            $pendingGrades = Grade::where('teacher_id', $teacher->id)->where('status_verifikasi', 'Menunggu Verifikasi')->count();

            $totalAll = $totalCapaian + $totalKualitas + $totalGrades;
            $pendingAll = $pendingCapaian + $pendingKualitas + $pendingGrades;
            $approvedAll = $totalAll - $pendingAll;

            $lastInputCapaian = QuranAchievement::where('teacher_id', $teacher->id)->latest('tanggal')->first()?->tanggal;
            $lastInputGrade = Grade::where('teacher_id', $teacher->id)->latest('tanggal')->first()?->tanggal;
            $lastInput = max($lastInputCapaian, $lastInputGrade);

            $monitoringGuru[] = [
                'teacher' => $teacher,
                'specialization' => $teacher->getQuranSpecializationLabel() ?? 'Al-Qur\'an',
                'classes' => !empty($tClasses) ? implode(', ', $tClasses) : 'Seluruh Kelas',
                'total_capaian' => $totalCapaian,
                'total_kualitas' => $totalKualitas,
                'total_grades' => $totalGrades,
                'total_all' => $totalAll,
                'pending_all' => $pendingAll,
                'approved_all' => $approvedAll,
                'last_input' => $lastInput ? \Carbon\Carbon::parse($lastInput)->format('d M Y') : '-',
                'status' => $totalAll === 0 ? 'Belum Ada Input' : ($pendingAll > 0 ? 'Menunggu Verifikasi' : 'Semua Diterima'),
            ];
        }

        return [
            'quranTeachers'  => $quranTeachers,
            'classrooms'     => $classrooms,
            'academicYears'  => $academicYears,
            'stats'          => $stats,
            'capaianData'    => $capaianData,
            'kualitasData'   => $kualitasData,
            'gradesData'     => $gradesData,
            'monitoringGuru' => $monitoringGuru,
            'surahs'         => QuranAchievementResource::getSurahs(),
        ];
    }
}
