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
    public string $modeNilai = 'olah'; // 'olah' (Nilai Rapot Olahan / Bantuan) | 'asli' (Nilai Rapot Asli Murni)

    // Modal preview rapor
    public ?int $previewStudentId = null;
    public ?array $previewRaporData = null;

    // Modal edit / kelola nilai bantuan (olahan) rapor siswa
    public bool $showEditRaporModal = false;
    public ?int $editingStudentId = null;
    public ?Student $editingStudent = null;
    public array $editingSubjectGrades = [];

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
        $this->closeEditRaporModal();
    }

    public function updatedSelectedAcademicYearId(): void
    {
        $this->closePreviewModal();
        $this->closeEditRaporModal();
    }

    public function updatedSelectedSemester(): void
    {
        $this->closePreviewModal();
        $this->closeEditRaporModal();
    }

    public function updatedModeNilai(): void
    {
        if ($this->previewStudentId) {
            $this->openPreviewRapor($this->previewStudentId);
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->closePreviewModal();
        $this->closeEditRaporModal();
    }

    public function openPreviewRapor(int $studentId): void
    {
        $student = Student::with(['classroom', 'institution'])->find($studentId);
        if (!$student) {
            Notification::make()->title('Data siswa tidak ditemukan')->danger()->send();
            return;
        }

        $this->previewStudentId = $studentId;
        $this->previewRaporData = RaporCalculationService::getRaporData($student, $this->selectedAcademicYearId, $this->selectedSemester, $this->modeNilai);
    }

    public function closePreviewModal(): void
    {
        $this->previewStudentId = null;
        $this->previewRaporData = null;
    }

    /**
     * Buka Modal Edit Nilai Olahan / Bantuan Rapor Siswa
     */
    public function openEditRaporModal(int $studentId): void
    {
        $student = Student::with(['classroom', 'institution'])->find($studentId);
        if (!$student) {
            Notification::make()->title('Data siswa tidak ditemukan')->danger()->send();
            return;
        }

        $this->editingStudentId = $studentId;
        $this->editingStudent = $student;

        // Ambil data rapor siswa saat ini
        $rapor = RaporCalculationService::getRaporData($student, $this->selectedAcademicYearId, $this->selectedSemester, 'olah');

        $this->editingSubjectGrades = [];
        foreach ($rapor['subjects'] as $sub) {
            $subId = $sub['subject']->id;
            $asliP = $sub['nilai_asli']['pengetahuan'];
            $asliK = $sub['nilai_asli']['keterampilan'];
            $olahP = $sub['nilai_olah']['pengetahuan'];
            $olahK = $sub['nilai_olah']['keterampilan'];

            $this->editingSubjectGrades[$subId] = [
                'subject_id'        => $subId,
                'name'              => $sub['name'],
                'asli_p'            => $asliP,
                'asli_k'            => $asliK,
                'asli_predikat_p'   => $sub['nilai_asli']['predikat_p'] ?: '-',
                'asli_predikat_k'   => $sub['nilai_asli']['predikat_k'] ?: '-',
                'olah_p'            => $olahP !== null ? (string)$olahP : '',
                'olah_k'            => $olahK !== null ? (string)$olahK : '',
                'catatan'           => $sub['catatan_bantuan'] ?? '',
                'is_edited'         => $sub['is_edited'],
            ];
        }

        $this->showEditRaporModal = true;
    }

    public function closeEditRaporModal(): void
    {
        $this->showEditRaporModal = false;
        $this->editingStudentId = null;
        $this->editingStudent = null;
        $this->editingSubjectGrades = [];
    }

    /**
     * Terapkan Bantuan KKM (75) untuk Siswa yang Sedang Diedit
     */
    public function applyAutoKkmForCurrentStudent(): void
    {
        $count = 0;
        foreach ($this->editingSubjectGrades as $subId => &$data) {
            $currentP = $data['olah_p'] !== '' ? (float)$data['olah_p'] : ($data['asli_p'] ?? null);
            $currentK = $data['olah_k'] !== '' ? (float)$data['olah_k'] : ($data['asli_k'] ?? null);

            $changed = false;
            if ($currentP !== null && $currentP < 75.0) {
                $data['olah_p'] = '75';
                $changed = true;
            }
            if ($currentK !== null && $currentK < 75.0) {
                $data['olah_k'] = '75';
                $changed = true;
            }

            if ($changed) {
                $data['catatan'] = 'Bantuan KKM (75)';
                $data['is_edited'] = true;
                $count++;
            }
        }
        unset($data);

        Notification::make()
            ->title("Bantuan KKM Diterapkan")
            ->body("Sebanyak {$count} mata pelajaran yang di bawah KKM disesuaikan menjadi 75.")
            ->info()
            ->send();
    }

    /**
     * Kembalikan Nilai Siswa ke Nilai Asli Murni Guru Mapel
     */
    public function resetCurrentStudentToAsli(): void
    {
        if (!$this->editingStudentId) return;

        RaporCalculationService::resetStudentToAsli($this->editingStudentId, $this->selectedAcademicYearId, $this->selectedSemester);

        // Muat ulang form modal
        $this->openEditRaporModal($this->editingStudentId);

        Notification::make()
            ->title("Nilai Direset ke Asli Murni")
            ->body("Seluruh nilai bantuan siswa telah dihapus dan kembali mengacu pada nilai asli guru mapel.")
            ->success()
            ->send();
    }

    /**
     * Simpan Nilai Olahan / Bantuan Rapor Siswa
     */
    public function saveStudentRaporGrades(): void
    {
        if (!$this->editingStudentId) return;

        foreach ($this->editingSubjectGrades as $subId => $item) {
            $valP = $item['olah_p'] !== '' && is_numeric($item['olah_p']) ? (float)$item['olah_p'] : null;
            $valK = $item['olah_k'] !== '' && is_numeric($item['olah_k']) ? (float)$item['olah_k'] : null;
            $catatan = trim($item['catatan'] ?? '');

            RaporCalculationService::saveRaporGradeAdjustment(
                $this->editingStudentId,
                (int)$subId,
                $this->selectedAcademicYearId,
                $this->selectedSemester,
                $valP,
                $valK,
                $catatan ?: null,
                null,
                null,
                Auth::id()
            );
        }

        Notification::make()
            ->title("✅ Nilai Rapor Berhasil Disimpan")
            ->body("Nilai olahan / bantuan rapor siswa {$this->editingStudent?->name} berhasil diperbarui.")
            ->success()
            ->send();

        $this->closeEditRaporModal();
    }

    /**
     * Katrol Otomatis Seluruh Nilai Bawah KKM untuk 1 Rombel Sekaligus
     */
    public function autoKatrolKelasMassal(): void
    {
        if (!$this->selectedClassroomId) {
            Notification::make()->title('Pilih kelas terlebih dahulu')->warning()->send();
            return;
        }

        $count = RaporCalculationService::autoBoostClassroomToKkm(
            $this->selectedClassroomId,
            $this->selectedAcademicYearId,
            $this->selectedSemester,
            75.0,
            Auth::id()
        );

        Notification::make()
            ->title("⚡ Katrol Massal Selesai")
            ->body("Sebanyak {$count} nilai siswa di bawah KKM berhasil diberikan nilai bantuan menjadi 75.")
            ->success()
            ->send();
    }

    /**
     * Reset Seluruh Nilai Kelas ke Nilai Asli Murni Guru Mapel
     */
    public function resetKelasMassal(): void
    {
        if (!$this->selectedClassroomId) {
            Notification::make()->title('Pilih kelas terlebih dahulu')->warning()->send();
            return;
        }

        $count = RaporCalculationService::resetClassroomToAsli(
            $this->selectedClassroomId,
            $this->selectedAcademicYearId,
            $this->selectedSemester
        );

        Notification::make()
            ->title("🔄 Reset Nilai Kelas Berhasil")
            ->body("Seluruh nilai bantuan kelas telah dibersihkan. Leger & rapor kembali menampilkan nilai asli murni.")
            ->info()
            ->send();
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
                $this->selectedSemester,
                $this->modeNilai
            );
        }

        return [
            'availableClasses' => $availableClasses,
            'academicYears'    => $academicYears,
            'selectedClass'    => $selectedClass,
            'legerData'        => $legerData,
            'modeNilai'        => $this->modeNilai,
        ];
    }
}
