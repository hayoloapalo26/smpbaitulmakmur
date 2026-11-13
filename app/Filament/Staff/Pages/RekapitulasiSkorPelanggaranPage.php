<?php

namespace App\Filament\Staff\Pages;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentViolationApproval;
use App\Models\StudentViolationLog;
use App\Models\StudentViolationRecord;
use App\Models\User;
use App\Services\StudentViolationReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class RekapitulasiSkorPelanggaranPage extends Page
{
    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'bk', 'wali_kelas', 'guru_kelas', 'guru_piket'])) {
            return true;
        }

        return $user->isPiketToday();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static string $view = 'filament.staff.pages.rekapitulasi-skor-pelanggaran';
    protected static ?string $title = 'Rekapitulasi Skor Poin Pelanggaran';
    protected static ?string $navigationLabel = 'Rekapitulasi Skor Poin Pelanggaran';
    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return '5 Periode';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationGroup(): ?string
    {
        $user = Auth::user();
        if ($user?->isPiketToday() && !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'bk'])) {
            return 'Tugas Guru Piket';
        }
        if ($user && $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'bk'])) {
            return 'Fitur Wali Kelas';
        }

        return 'Kesiswaan & Karakter';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label(fn () => 'Unduh PDF ' . ucfirst($this->activeTab))
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn () => route('academic.violation.rekap.pdf', [
                    'tab'          => $this->activeTab,
                    'classroom_id' => $this->selectedClassroomId,
                    'tanggal'      => $this->selectedTanggal,
                    'tahun'        => $this->selectedTahun,
                    'bulan'        => $this->selectedBulan,
                    'minggu'       => $this->selectedMinggu,
                    'triwulan'     => $this->selectedTriwulan,
                    'semester'     => $this->selectedSemester,
                    'tahun_ajaran' => $this->selectedTahunAjaran,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    // Tab aktif: harian | mingguan | bulanan | triwulan | semester
    public string $activeTab = 'harian';

    // Filter umum
    public ?int $selectedClassroomId = null;
    public string $selectedTanggal = '';
    public int $selectedTahun = 2026;
    public int $selectedBulan = 11;
    public int $selectedMinggu = 2;
    public int $selectedTriwulan = 1;
    public string $selectedTahunAjaran = '2026/2027';
    public string $selectedSemester = 'ganjil';

    // State Modal Input Jurnal Harian (Guru Piket)
    public bool $showViolationModal = false;
    public ?int $editingViolationId = null;
    public ?int $formStudentId = null;
    public string $formTanggal = '';
    public string $formJam = '07:30';
    public string $formBentukPelanggaran = '';
    public int $formPoin = 5;
    public ?int $formTeacherId = null;
    public string $formTindakLanjut = '';

    // State Modal Input Catatan Berkala (Wali Kelas / BK / Kesiswaan)
    public bool $showRecordModal = false;
    public ?int $recordStudentId = null;
    public string $recordStudentName = '';
    public string $recordType = '';
    public string $recordKey = '';
    public string $fieldCatatanWaliKelas = '';
    public string $fieldStatusSanksiBk = '';
    public string $fieldTindakLanjutBk = '';
    public string $fieldStatusSanksiKesiswaan = '';
    public string $fieldTindakLanjutKesiswaan = '';

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $today = Carbon::today();
        $this->selectedTanggal = $today->toDateString();
        $this->selectedTahun = (int) $today->format('Y');
        $this->selectedBulan = (int) $today->format('m');
        $this->selectedMinggu = min(5, (int) ceil((int) $today->format('d') / 7));

        // Tentukan triwulan default
        $this->selectedTriwulan = match (true) {
            $this->selectedBulan >= 7 && $this->selectedBulan <= 9   => 1,
            $this->selectedBulan >= 10 && $this->selectedBulan <= 12 => 2,
            $this->selectedBulan >= 1 && $this->selectedBulan <= 3   => 3,
            default => 4,
        };

        // Tentukan semester default
        $this->selectedSemester = ($this->selectedBulan >= 7 && $this->selectedBulan <= 12) ? 'ganjil' : 'genap';

        // Filter kelas berdasarkan hak akses Wali Kelas
        $isRestrictedWaliKelas = $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'bk']);

        if ($isRestrictedWaliKelas) {
            $myClass = Classroom::reguler()->where('wali_kelas_id', $user->id)->first()
                ?? Classroom::where('wali_kelas_id', $user->id)->first();
            $this->selectedClassroomId = $myClass?->id;
        } else {
            $firstClass = Classroom::reguler()->orderBy('name')->first() ?? Classroom::orderBy('name')->first();
            $this->selectedClassroomId = $firstClass?->id;
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function setPresetPoin(int $poin): void
    {
        $this->formPoin = $poin;
    }

    /**
     * Buka Modal Tambah/Edit Jurnal Harian Pelanggaran
     */
    public function openCreateViolationModal(?int $id = null): void
    {
        $this->editingViolationId = $id;

        if ($id) {
            $log = StudentViolationLog::findOrFail($id);
            $this->formStudentId = $log->student_id;
            $this->formTanggal = $log->tanggal?->toDateString() ?? $this->selectedTanggal;
            $this->formJam = $log->jam;
            $this->formBentukPelanggaran = $log->bentuk_pelanggaran;
            $this->formPoin = $log->poin;
            $this->formTeacherId = $log->teacher_id;
            $this->formTindakLanjut = $log->tindak_lanjut_langsung ?? '';
        } else {
            $this->formStudentId = null;
            $this->formTanggal = $this->selectedTanggal ?: date('Y-m-d');
            $this->formJam = date('H:i');
            $this->formBentukPelanggaran = '';
            $this->formPoin = 5;
            $this->formTeacherId = Auth::id();
            $this->formTindakLanjut = '';
        }

        $this->showViolationModal = true;
    }

    public function closeViolationModal(): void
    {
        $this->showViolationModal = false;
        $this->editingViolationId = null;
    }

    /**
     * Simpan Jurnal Harian Pelanggaran (Guru Piket)
     */
    public function saveViolation(): void
    {
        $this->validate([
            'formStudentId'         => 'required|exists:students,id',
            'formTanggal'           => 'required|date',
            'formJam'               => 'required|string',
            'formBentukPelanggaran' => 'required|string|min:3',
            'formPoin'              => 'required|integer|min:1',
            'formTeacherId'         => 'required|exists:users,id',
            'formTindakLanjut'      => 'nullable|string',
        ], [
            'formStudentId.required'         => 'Wajib memilih nama murid.',
            'formTanggal.required'           => 'Wajib memilih tanggal kejadian.',
            'formBentukPelanggaran.required' => 'Bentuk / Deskripsi pelanggaran wajib diisi.',
            'formPoin.required'              => 'Poin pelanggaran wajib diisi.',
        ]);

        $student = Student::find($this->formStudentId);

        $payload = [
            'student_id'             => $this->formStudentId,
            'classroom_id'           => $student?->classroom_id,
            'tanggal'                => $this->formTanggal,
            'jam'                    => $this->formJam,
            'bentuk_pelanggaran'     => $this->formBentukPelanggaran,
            'poin'                   => $this->formPoin,
            'teacher_id'             => $this->formTeacherId,
            'tindak_lanjut_langsung' => $this->formTindakLanjut ?: null,
        ];

        if ($this->editingViolationId) {
            $log = StudentViolationLog::findOrFail($this->editingViolationId);
            $log->update($payload);
            Notification::make()->title('Catatan pelanggaran harian berhasil diperbarui.')->success()->send();
        } else {
            StudentViolationLog::create($payload);
            Notification::make()->title('Catatan pelanggaran harian berhasil ditambahkan oleh Guru Piket.')->success()->send();
        }

        $this->closeViolationModal();
    }

    /**
     * Hapus Pelanggaran Harian
     */
    public function deleteViolation(int $id): void
    {
        $user = Auth::user();
        $log = StudentViolationLog::findOrFail($id);

        if (!$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']) && $log->teacher_id !== $user->id) {
            Notification::make()->title('Akses ditolak: Anda hanya dapat menghapus catatan yang Anda buat sendiri.')->danger()->send();
            return;
        }

        $log->delete();
        Notification::make()->title('Catatan pelanggaran berhasil dihapus.')->success()->send();
    }

    /**
     * Buka Modal Edit Catatan Khusus Stakeholder (Wali Kelas / BK / Kesiswaan)
     */
    public function openRecordModal(int $studentId, string $type, string $key): void
    {
        $student = Student::findOrFail($studentId);
        $this->recordStudentId = $studentId;
        $this->recordStudentName = $student->name;
        $this->recordType = $type;
        $this->recordKey = $key;

        $record = StudentViolationRecord::where('student_id', $studentId)
            ->where('periode_type', $type)
            ->where('periode_key', $key)
            ->first();

        $this->fieldCatatanWaliKelas = $record?->catatan_wali_kelas ?? '';
        $this->fieldStatusSanksiBk = $record?->status_sanksi_bk ?? '';
        $this->fieldTindakLanjutBk = $record?->tindak_lanjut_bk ?? '';
        $this->fieldStatusSanksiKesiswaan = $record?->status_sanksi_kesiswaan ?? '';
        $this->fieldTindakLanjutKesiswaan = $record?->tindak_lanjut_kesiswaan ?? '';

        $this->showRecordModal = true;
    }

    public function closeRecordModal(): void
    {
        $this->showRecordModal = false;
        $this->recordStudentId = null;
    }

    /**
     * Simpan Catatan Khusus Stakeholder
     */
    public function saveRecord(): void
    {
        if (!$this->recordStudentId) {
            return;
        }

        $student = Student::find($this->recordStudentId);

        StudentViolationRecord::updateOrCreate(
            [
                'student_id'   => $this->recordStudentId,
                'periode_type' => $this->recordType,
                'periode_key'  => $this->recordKey,
            ],
            [
                'classroom_id'            => $student?->classroom_id,
                'catatan_wali_kelas'      => $this->fieldCatatanWaliKelas ?: null,
                'status_sanksi_bk'        => $this->fieldStatusSanksiBk ?: null,
                'tindak_lanjut_bk'        => $this->fieldTindakLanjutBk ?: null,
                'status_sanksi_kesiswaan' => $this->fieldStatusSanksiKesiswaan ?: null,
                'tindak_lanjut_kesiswaan' => $this->fieldTindakLanjutKesiswaan ?: null,
                'user_id'                 => Auth::id(),
            ]
        );

        Notification::make()->title('Catatan & tindak lanjut berhasil disimpan.')->success()->send();
        $this->closeRecordModal();
    }

    /**
     * Eksekusi ACC Berdasarkan Peran & Periode
     */
    public function toggleAcc(string $roleKey): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        // Validasi wewenang ACC
        $isAuthorized = match ($roleKey) {
            'piket' => $user->isPiketToday() || $user->hasRole('guru_piket') || $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']),
            'wali_kelas' => ($this->selectedClassroomId && Classroom::where('id', $this->selectedClassroomId)->where('wali_kelas_id', $user->id)->exists()) || $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']),
            'bk' => $user->hasRole('bk') || $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']),
            'waka_kesiswaan' => $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']),
            'kepala_sekolah' => $user->hasAnyRole(['kepala_sekolah', 'superadmin']),
            default => false,
        };

        if (!$isAuthorized) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki wewenang untuk melakukan ACC pada peran ini.')
                ->danger()
                ->send();
            return;
        }

        // Tentukan periodeKey berdasarkan tab aktif
        $periodeKey = match ($this->activeTab) {
            'harian'   => $this->selectedTanggal,
            'mingguan' => "{$this->selectedTahun}-M{$this->selectedBulan}-W{$this->selectedMinggu}",
            'bulanan'  => sprintf('%04d-%02d', $this->selectedTahun, $this->selectedBulan),
            'triwulan' => "{$this->selectedTahun}-TW{$this->selectedTriwulan}",
            'semester' => "{$this->selectedTahunAjaran}-{$this->selectedSemester}",
        };

        $approval = StudentViolationApproval::getApproval($this->selectedClassroomId, $this->activeTab, $periodeKey);

        $flagCol = "acc_{$roleKey}";
        $byCol = "acc_{$roleKey}_by";
        $atCol = "acc_{$roleKey}_at";

        $currentStatus = $approval->$flagCol ?? false;
        $newStatus = !$currentStatus;

        $approval->$flagCol = $newStatus;
        $approval->$byCol = $newStatus ? $user->id : null;
        $approval->$atCol = $newStatus ? now() : null;
        $approval->save();

        if ($newStatus) {
            Notification::make()
                ->title('ACC Berhasil Disetujui')
                ->body("Dokumen rekapitulasi telah di-ACC resmi oleh {$user->name}.")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Persetujuan ACC Dibatalkan')
                ->body("Status ACC berhasil ditarik kembali.")
                ->warning()
                ->send();
        }
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $isRestrictedWaliKelas = $user && $user->hasAnyRole(['wali_kelas', 'guru_kelas']) && !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'bk']);

        if ($isRestrictedWaliKelas) {
            $availableClasses = Classroom::reguler()->where('wali_kelas_id', $user->id)->orderBy('name')->get();
            if ($availableClasses->isEmpty()) {
                $availableClasses = Classroom::where('wali_kelas_id', $user->id)->orderBy('name')->get();
            }
        } else {
            $query = Classroom::reguler()->orderBy('name');
            if ($user && !$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
                $query->where('institution_id', $user->institution_id);
            }
            $availableClasses = $query->get();
            if ($availableClasses->isEmpty()) {
                $availableClasses = Classroom::orderBy('name')->get();
            }
        }

        $selectedClass = $this->selectedClassroomId ? Classroom::with('waliKelas')->find($this->selectedClassroomId) : null;

        // Ambil data sesuai tab aktif
        $tableData = collect();
        $bulanHeaders = [];
        $currentPeriodeKey = '';

        if ($this->activeTab === 'harian') {
            $currentPeriodeKey = $this->selectedTanggal;
            $tableData = StudentViolationReportService::getHarianData($this->selectedTanggal, $this->selectedClassroomId);
        } elseif ($this->activeTab === 'mingguan') {
            $currentPeriodeKey = "{$this->selectedTahun}-M{$this->selectedBulan}-W{$this->selectedMinggu}";
            $tableData = StudentViolationReportService::getMingguanData($this->selectedTahun, $this->selectedBulan, $this->selectedMinggu, $this->selectedClassroomId);
        } elseif ($this->activeTab === 'bulanan') {
            $currentPeriodeKey = sprintf('%04d-%02d', $this->selectedTahun, $this->selectedBulan);
            $tableData = StudentViolationReportService::getBulananData($this->selectedTahun, $this->selectedBulan, $this->selectedClassroomId);
        } elseif ($this->activeTab === 'triwulan') {
            $result = StudentViolationReportService::getTriwulanData($this->selectedTahun, $this->selectedTriwulan, $this->selectedClassroomId);
            $tableData = $result['rows'];
            $bulanHeaders = $result['monthNames'];
            $currentPeriodeKey = $result['periodeKey'];
        } elseif ($this->activeTab === 'semester') {
            $result = StudentViolationReportService::getSemesterData($this->selectedTahunAjaran, $this->selectedSemester, $this->selectedClassroomId);
            $tableData = $result['rows'];
            $bulanHeaders = $result['monthNames'];
            $currentPeriodeKey = $result['periodeKey'];
        }

        $approval = StudentViolationApproval::where('classroom_id', $this->selectedClassroomId)
            ->where('periode_type', $this->activeTab)
            ->where('periode_key', $currentPeriodeKey)
            ->with(['piketUser', 'waliKelasUser', 'bkUser', 'kesiswaanUser', 'kepsekUser'])
            ->first();

        // Hitung statistik ringkas untuk kartu atas
        $totalKasus = 0;
        $totalPoin = 0;
        $siswaMelanggar = 0;

        if ($this->activeTab === 'harian') {
            $totalKasus = $tableData->count();
            $totalPoin = (int) $tableData->sum('poin');
            $siswaMelanggar = $tableData->pluck('student_id')->unique()->count();
        } elseif ($this->activeTab === 'mingguan') {
            $filtered = $tableData->where('total_poin', '>', 0);
            $totalKasus = $filtered->count();
            $totalPoin = (int) $tableData->sum('total_poin');
            $siswaMelanggar = $filtered->count();
        } elseif ($this->activeTab === 'bulanan') {
            $filtered = $tableData->where('total_akumulasi', '>', 0);
            $totalKasus = $filtered->count();
            $totalPoin = (int) $tableData->sum('total_akumulasi');
            $siswaMelanggar = $filtered->count();
        } elseif ($this->activeTab === 'triwulan') {
            $filtered = $tableData->where('total_3_bulan', '>', 0);
            $totalKasus = $filtered->count();
            $totalPoin = (int) $tableData->sum('total_3_bulan');
            $siswaMelanggar = $filtered->count();
        } elseif ($this->activeTab === 'semester') {
            $filtered = $tableData->where('total_semester', '>', 0);
            $totalKasus = $filtered->count();
            $totalPoin = (int) $tableData->sum('total_semester');
            $siswaMelanggar = $filtered->count();
        }

        // Daftar siswa untuk modal select dengan nama kelas
        $studentOptions = Student::query()
            ->with('classroom')
            ->when($this->selectedClassroomId, fn ($q) => $q->where('classroom_id', $this->selectedClassroomId))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($s) {
                $cls = $s->classroom->name ?? 'Tanpa Kelas';
                return [$s->id => "{$s->name} ({$cls})"];
            });

        return [
            'isRestrictedWaliKelas' => $isRestrictedWaliKelas,
            'availableClasses'      => $availableClasses,
            'selectedClass'         => $selectedClass,
            'tableData'             => $tableData,
            'bulanHeaders'          => $bulanHeaders,
            'approval'              => $approval,
            'currentPeriodeKey'     => $currentPeriodeKey,
            'studentOptions'        => $studentOptions,
            'currentUser'           => $user,
            'stats'                 => [
                'totalKasus'      => $totalKasus,
                'totalPoin'       => $totalPoin,
                'siswaMelanggar'  => $siswaMelanggar,
                'totalSiswaKelas' => $studentOptions->count(),
            ],
        ];
    }
}
