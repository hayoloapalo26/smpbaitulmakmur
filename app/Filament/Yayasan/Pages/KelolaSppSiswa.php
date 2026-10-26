<?php

namespace App\Filament\Yayasan\Pages;

use Filament\Pages\Page;
use App\Models\Institution;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentSpp;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class KelolaSppSiswa extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Input SPP Per Kelas';
    protected static ?string $title = 'Pengelolaan SPP Siswa (Lintas Lembaga)';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.yayasan.pages.kelola-spp-siswa';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        return $user->hasAnyRole([
            'superadmin',
            'pegawai_keuangan_yayasan',
            'pegawai_keuangan',
            'ketua_yayasan',
            'pembina_yayasan',
            'sekretaris_yayasan',
            'yayasan',
        ]);
    }

    // Filters
    public ?int $selectedInstitutionId = null;
    public ?int $selectedClassroomId = null;
    public string $selectedTahunAjaran = '2026/2027';
    public string $selectedBulan = 'Oktober';
    public string $search = '';
    public string $statusFilter = 'all'; // 'all', 'lunas', 'belum_lunas'
    public int $defaultNominal = 200000;

    // Bulk selection
    public array $selectedStudents = [];
    public bool $selectAll = false;

    // Edit modal state
    public bool $showEditModal = false;
    public ?int $editStudentId = null;
    public string $editStudentName = '';
    public string $editStudentNis = '';
    public string $editStatus = 'lunas';
    public ?string $editTanggalBayar = null;
    public ?int $editNominal = null;
    public string $editMetode = 'Tunai';
    public ?string $editKwitansi = null;
    public ?string $editCatatan = null;

    public function mount(): void
    {
        // Auto-select current month
        $bulanMap = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $this->selectedBulan = $bulanMap[(int)date('n')] ?? 'Oktober';

        // Auto select first school institution
        $firstInst = Institution::where('id', '>', 1)->orderBy('id')->first();
        if ($firstInst) {
            $this->selectedInstitutionId = $firstInst->id;
            $firstClass = Classroom::withoutGlobalScopes()
                ->where('institution_id', $firstInst->id)
                ->orderBy('name')
                ->first();
            if ($firstClass) {
                $this->selectedClassroomId = $firstClass->id;
            }
        }
    }

    public function updatedSelectedInstitutionId(): void
    {
        $this->selectedClassroomId = null;
        $this->selectedStudents = [];
        $this->selectAll = false;

        // Auto select first class in this institution if available
        if ($this->selectedInstitutionId) {
            $firstClass = Classroom::withoutGlobalScopes()
                ->where('institution_id', $this->selectedInstitutionId)
                ->orderBy('name')
                ->first();
            if ($firstClass) {
                $this->selectedClassroomId = $firstClass->id;
            }
        }
    }

    public function updatedSelectedClassroomId(): void
    {
        $this->selectedStudents = [];
        $this->selectAll = false;
    }

    public function updatedSelectedBulan(): void
    {
        $this->selectedStudents = [];
        $this->selectAll = false;
    }

    public function updatedSelectedTahunAjaran(): void
    {
        $this->selectedStudents = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedStudents = $this->getStudentsProperty()
                ->pluck('id')
                ->map(fn($id) => (int)$id)
                ->toArray();
        } else {
            $this->selectedStudents = [];
        }
    }

    public function getInstitutionsProperty(): Collection
    {
        return Institution::where('id', '>', 1)->orderBy('id')->get();
    }

    public function getClassroomsProperty(): Collection
    {
        if (!$this->selectedInstitutionId) {
            return collect();
        }

        return Classroom::withoutGlobalScopes()
            ->where('institution_id', $this->selectedInstitutionId)
            ->orderBy('name')
            ->get();
    }

    public function getStudentsProperty(): Collection
    {
        if (!$this->selectedClassroomId) {
            return collect();
        }

        $query = Student::withoutGlobalScopes()
            ->where('classroom_id', $this->selectedClassroomId);

        if (!empty(trim($this->search))) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('name')->get();

        // Fetch SPP payments for this class, academic year, and month
        $sppRecords = StudentSpp::where('classroom_id', $this->selectedClassroomId)
            ->where('tahun_ajaran', $this->selectedTahunAjaran)
            ->where('bulan', $this->selectedBulan)
            ->get()
            ->keyBy('student_id');

        // Map SPP record onto student models
        $mapped = $students->map(function ($student) use ($sppRecords) {
            $spp = $sppRecords->get($student->id);
            $student->spp_record = $spp;
            $student->is_lunas = $spp ? ($spp->status === 'lunas') : false;
            $student->spp_nominal = $spp?->nominal ?? $this->defaultNominal;
            $student->spp_tanggal_bayar = $spp?->tanggal_bayar?->format('d M Y') ?? null;
            $student->spp_metode = $spp?->metode_pembayaran ?? '-';
            $student->spp_kwitansi = $spp?->kwitansi_no ?? '-';
            $student->spp_catatan = $spp?->catatan ?? '';
            return $student;
        });

        // Apply status filter
        if ($this->statusFilter === 'lunas') {
            return $mapped->filter(fn($s) => $s->is_lunas);
        } elseif ($this->statusFilter === 'belum_lunas') {
            return $mapped->filter(fn($s) => !$s->is_lunas);
        }

        return $mapped;
    }

    public function getStatsProperty(): array
    {
        if (!$this->selectedClassroomId) {
            return [
                'total' => 0,
                'lunas' => 0,
                'belum_lunas' => 0,
                'persentase' => 0,
                'total_nominal' => 0,
            ];
        }

        $allStudentsInClass = Student::withoutGlobalScopes()
            ->where('classroom_id', $this->selectedClassroomId)
            ->get();

        $totalCount = $allStudentsInClass->count();
        if ($totalCount === 0) {
            return [
                'total' => 0,
                'lunas' => 0,
                'belum_lunas' => 0,
                'persentase' => 0,
                'total_nominal' => 0,
            ];
        }

        $sppRecords = StudentSpp::where('classroom_id', $this->selectedClassroomId)
            ->where('tahun_ajaran', $this->selectedTahunAjaran)
            ->where('bulan', $this->selectedBulan)
            ->where('status', 'lunas')
            ->get();

        $lunasCount = $sppRecords->count();
        $belumLunasCount = max(0, $totalCount - $lunasCount);
        $persentase = round(($lunasCount / $totalCount) * 100);
        $totalNominal = $sppRecords->sum('nominal');

        return [
            'total' => $totalCount,
            'lunas' => $lunasCount,
            'belum_lunas' => $belumLunasCount,
            'persentase' => $persentase,
            'total_nominal' => $totalNominal,
        ];
    }

    public function togglePaymentStatus(int $studentId): void
    {
        $student = Student::withoutGlobalScopes()->findOrFail($studentId);

        $spp = StudentSpp::where('student_id', $student->id)
            ->where('tahun_ajaran', $this->selectedTahunAjaran)
            ->where('bulan', $this->selectedBulan)
            ->first();

        if ($spp && $spp->status === 'lunas') {
            // Ubah menjadi belum lunas
            $spp->update([
                'status' => 'belum_lunas',
                'tanggal_bayar' => null,
            ]);

            Notification::make()
                ->title("SPP {$student->name} dibatalkan (Belum Lunas)")
                ->warning()
                ->send();
        } else {
            // Ubah menjadi lunas
            StudentSpp::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'tahun_ajaran' => $this->selectedTahunAjaran,
                    'bulan' => $this->selectedBulan,
                ],
                [
                    'institution_id' => $student->institution_id,
                    'classroom_id' => $student->classroom_id,
                    'status' => 'lunas',
                    'nominal' => $this->defaultNominal,
                    'tanggal_bayar' => now()->toDateString(),
                    'metode_pembayaran' => 'Tunai',
                    'petugas_id' => Auth::id(),
                ]
            );

            Notification::make()
                ->title("SPP {$student->name} berhasil ditandai LUNAS")
                ->success()
                ->send();
        }
    }

    public function openEditPaymentModal(int $studentId): void
    {
        $student = Student::withoutGlobalScopes()->findOrFail($studentId);
        $spp = StudentSpp::where('student_id', $student->id)
            ->where('tahun_ajaran', $this->selectedTahunAjaran)
            ->where('bulan', $this->selectedBulan)
            ->first();

        $this->editStudentId = $student->id;
        $this->editStudentName = $student->name;
        $this->editStudentNis = $student->nis ?? '-';
        $this->editStatus = $spp ? $spp->status : 'lunas';
        $this->editTanggalBayar = $spp?->tanggal_bayar?->toDateString() ?? now()->toDateString();
        $this->editNominal = $spp ? (int)$spp->nominal : $this->defaultNominal;
        $this->editMetode = $spp?->metode_pembayaran ?? 'Tunai';
        $this->editKwitansi = $spp?->kwitansi_no ?? 'SPP/' . date('Ym') . '/' . $student->nis;
        $this->editCatatan = $spp?->catatan ?? '';

        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetEditModal();
    }

    private function resetEditModal(): void
    {
        $this->editStudentId = null;
        $this->editStudentName = '';
        $this->editStudentNis = '';
        $this->editStatus = 'lunas';
        $this->editTanggalBayar = null;
        $this->editNominal = null;
        $this->editMetode = 'Tunai';
        $this->editKwitansi = null;
        $this->editCatatan = null;
    }

    public function savePaymentModal(): void
    {
        if (!$this->editStudentId) return;

        $student = Student::withoutGlobalScopes()->findOrFail($this->editStudentId);

        StudentSpp::updateOrCreate(
            [
                'student_id' => $student->id,
                'tahun_ajaran' => $this->selectedTahunAjaran,
                'bulan' => $this->selectedBulan,
            ],
            [
                'institution_id' => $student->institution_id,
                'classroom_id' => $student->classroom_id,
                'status' => $this->editStatus,
                'nominal' => $this->editNominal ?? $this->defaultNominal,
                'tanggal_bayar' => $this->editStatus === 'lunas' ? ($this->editTanggalBayar ?? now()->toDateString()) : null,
                'metode_pembayaran' => $this->editMetode,
                'kwitansi_no' => $this->editKwitansi,
                'catatan' => $this->editCatatan,
                'petugas_id' => Auth::id(),
            ]
        );

        $this->showEditModal = false;
        $this->resetEditModal();

        Notification::make()
            ->title("Data pembayaran SPP {$student->name} berhasil diperbarui")
            ->success()
            ->send();
    }

    public function bulkMarkPaid(): void
    {
        if (empty($this->selectedStudents)) {
            Notification::make()
                ->title('Pilih setidaknya satu siswa terlebih dahulu')
                ->warning()
                ->send();
            return;
        }

        $students = Student::withoutGlobalScopes()
            ->whereIn('id', $this->selectedStudents)
            ->get();

        foreach ($students as $student) {
            StudentSpp::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'tahun_ajaran' => $this->selectedTahunAjaran,
                    'bulan' => $this->selectedBulan,
                ],
                [
                    'institution_id' => $student->institution_id,
                    'classroom_id' => $student->classroom_id,
                    'status' => 'lunas',
                    'nominal' => $this->defaultNominal,
                    'tanggal_bayar' => now()->toDateString(),
                    'metode_pembayaran' => 'Tunai',
                    'petugas_id' => Auth::id(),
                ]
            );
        }

        $count = count($this->selectedStudents);
        $this->selectedStudents = [];
        $this->selectAll = false;

        Notification::make()
            ->title("Berhasil menandai LUNAS sebanyak {$count} siswa")
            ->success()
            ->send();
    }

    public function bulkMarkUnpaid(): void
    {
        if (empty($this->selectedStudents)) {
            Notification::make()
                ->title('Pilih setidaknya satu siswa terlebih dahulu')
                ->warning()
                ->send();
            return;
        }

        StudentSpp::whereIn('student_id', $this->selectedStudents)
            ->where('tahun_ajaran', $this->selectedTahunAjaran)
            ->where('bulan', $this->selectedBulan)
            ->update([
                'status' => 'belum_lunas',
                'tanggal_bayar' => null,
            ]);

        $count = count($this->selectedStudents);
        $this->selectedStudents = [];
        $this->selectAll = false;

        Notification::make()
            ->title("Berhasil mengubah {$count} siswa menjadi BELUM LUNAS")
            ->warning()
            ->send();
    }

    public function markAllInClassPaid(): void
    {
        if (!$this->selectedClassroomId) return;

        $students = Student::withoutGlobalScopes()
            ->where('classroom_id', $this->selectedClassroomId)
            ->get();

        if ($students->isEmpty()) {
            Notification::make()
                ->title('Tidak ada siswa di kelas ini')
                ->warning()
                ->send();
            return;
        }

        foreach ($students as $student) {
            StudentSpp::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'tahun_ajaran' => $this->selectedTahunAjaran,
                    'bulan' => $this->selectedBulan,
                ],
                [
                    'institution_id' => $student->institution_id,
                    'classroom_id' => $student->classroom_id,
                    'status' => 'lunas',
                    'nominal' => $this->defaultNominal,
                    'tanggal_bayar' => now()->toDateString(),
                    'metode_pembayaran' => 'Tunai',
                    'petugas_id' => Auth::id(),
                ]
            );
        }

        Notification::make()
            ->title("Semua siswa di kelas ini ({$students->count()} siswa) berhasil ditandai LUNAS!")
            ->success()
            ->send();
    }
}
