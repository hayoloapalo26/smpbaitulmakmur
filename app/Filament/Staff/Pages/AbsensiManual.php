<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\DailyAttendance;
use App\Models\StudentHalangan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class AbsensiManual extends Page
{
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user || $user->isQuranTeacher()) {
            return false;
        }

        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin', 'tata_usaha'])) {
            return true;
        }

        return $user->isPiketToday();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected static ?string $navigationIcon    = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup   = 'Tugas Guru Piket';
    protected static ?string $navigationLabel   = 'Input Presensi Manual Siswa';
    protected static ?string $title             = 'Input Presensi Manual Siswa (Cadangan Mati Lampu)';
    protected static string  $view              = 'filament.staff.pages.absensi-manual';
    protected static ?int    $navigationSort    = 3;

    // Filter state
    public string $selectedDate;
    public string $tipeAbsen = 'masuk';
    public ?int   $selectedClassroomId = null;

    // Attendance state: [student_id => status]
    public array $attendanceData = [];

    // Attendance metadata: [student_id => ['metode' => ..., 'jam' => ..., 'is_late' => ..., 'catatan' => ...]]
    public array $attendanceMeta = [];

    public function mount(): void
    {
        $this->selectedDate = Carbon::today()->toDateString();

        // Default: kelas pertama berdasarkan hak akses/institusi
        $firstClass = $this->getClassrooms()->first();
        $this->selectedClassroomId = $firstClass?->id;

        $this->loadAttendance();
    }

    public function updatedSelectedClassroomId(): void
    {
        $this->loadAttendance();
    }

    public function updatedTipeAbsen(): void
    {
        $this->loadAttendance();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadAttendance();
    }

    /**
     * Muat data presensi yang sudah ada untuk tampilan default (Terintegrasi Fingerprint & Manual)
     */
    public function loadAttendance(): void
    {
        if (!$this->selectedClassroomId) {
            return;
        }

        $students = Student::where('classroom_id', $this->selectedClassroomId)
            ->orderBy('name')
            ->get();

        $existingRecords = DailyAttendance::where('tanggal', $this->selectedDate)
            ->where('tipe_absen', $this->tipeAbsen)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        // Default status: belum diisi = null, sudah ada = ambil dari DB
        $this->attendanceData = [];
        $this->attendanceMeta = [];

        foreach ($students as $student) {
            if ($existingRecords->has($student->id)) {
                $rec = $existingRecords[$student->id];
                $this->attendanceData[$student->id] = $rec->status;
                $this->attendanceMeta[$student->id] = [
                    'metode'  => $rec->metode_absen ?? 'manual',
                    'jam'     => $rec->waktu_kedatangan ? Carbon::parse($rec->waktu_kedatangan)->format('H:i') : null,
                    'is_late' => (bool) $rec->is_late,
                    'catatan' => $rec->keterangan ?? $rec->catatan ?? null,
                ];
            } else {
                // Cek apakah siswa sudah tercatat Sakit, Izin, atau Alpha dari presensi pagi piket
                $morningAbsence = in_array($this->tipeAbsen, ['sholat_dhuhur', 'sholat_ashar'])
                    ? DailyAttendance::where('student_id', $student->id)
                        ->where('tanggal', $this->selectedDate)
                        ->where('tipe_absen', 'masuk')
                        ->whereIn('status', ['sakit', 'izin', 'alpa'])
                        ->first()
                    : null;

                if ($morningAbsence) {
                    $this->attendanceData[$student->id] = $morningAbsence->status;
                    $this->attendanceMeta[$student->id] = [
                        'metode'  => 'manual',
                        'jam'     => null,
                        'is_late' => false,
                        'catatan' => "Otomatis ({$morningAbsence->status}) dari Presensi Pagi Piket",
                    ];
                } elseif ($student->jenis_kelamin === 'P'
                    && in_array($this->tipeAbsen, ['sholat_dhuhur', 'sholat_ashar'])
                    && StudentHalangan::isHalangan($student->id, $this->selectedDate)
                ) {
                    $this->attendanceData[$student->id] = 'halangan';
                    $this->attendanceMeta[$student->id] = [
                        'metode'  => 'manual',
                        'jam'     => null,
                        'is_late' => false,
                        'catatan' => 'Udzur syar\'i (Halangan / Haid)',
                    ];
                } else {
                    $this->attendanceData[$student->id] = null; // belum diisi
                    $this->attendanceMeta[$student->id] = null;
                }
            }
        }
    }

    /**
     * Simpan semua data presensi manual (Terintegrasi dengan mesin Fingerprint)
     */
    public function simpan(): void
    {
        $savedCount   = 0;
        $updatedCount = 0;
        $now = Carbon::now()->format('H:i:s');
        $currentUser = Auth::user();

        foreach ($this->attendanceData as $studentId => $status) {
            if ($status === null || $status === '') {
                continue;
            }

            $student = Student::find($studentId);
            $institutionId = $student?->classroom?->institution_id 
                ?? $student?->institution_id 
                ?? ($currentUser?->institution_id ?? 1);

            $existing = DailyAttendance::where('student_id', $studentId)
                ->where('tanggal', $this->selectedDate)
                ->where('tipe_absen', $this->tipeAbsen)
                ->first();

            // Jika siswa sudah terdata lewat mesin fingerprint dan statusnya tetap hadir,
            // jaga integritas data fingerprint (jangan overwrite metode 'fingerprint' atau jam kedatangan aslinya)
            if ($existing && $existing->metode_absen === 'fingerprint' && $existing->status === $status) {
                continue;
            }

            $metode = ($existing && $existing->metode_absen === 'fingerprint' && $existing->status === $status)
                ? 'fingerprint'
                : 'manual';

            $keterangan = ($existing && $existing->metode_absen === 'fingerprint')
                ? $existing->keterangan
                : ('Input manual Guru Piket: ' . ($currentUser?->name ?? 'Petugas') . ' (Mati Lampu/Darurat)');

            $data = [
                'student_id'     => $studentId,
                'institution_id' => $institutionId,
                'teacher_id'     => $currentUser?->id ?? 1,
                'tanggal'        => $this->selectedDate,
                'tipe_absen'     => $this->tipeAbsen,
                'metode_absen'   => $metode,
                'status'         => $status,
                'is_late'        => ($existing?->is_late ?? ($this->tipeAbsen === 'masuk' && $status === 'hadir' && $now > '07:15:00')),
                'keterangan'     => $keterangan,
            ];

            if ($this->tipeAbsen === 'masuk' && $status === 'hadir') {
                $data['waktu_kedatangan'] = $existing?->waktu_kedatangan ?? $now;
            }

            if ($existing) {
                $existing->update($data);
                $updatedCount++;
            } else {
                DailyAttendance::create($data);
                $savedCount++;
            }

            // Jika siswi ditandai status halangan, otomatis buat catatan StudentHalangan agar konsisten di seluruh sistem
            if ($student && $student->jenis_kelamin === 'P' && $status === 'halangan') {
                StudentHalangan::firstOrCreate(
                    [
                        'student_id'     => $studentId,
                        'tanggal_mulai'  => $this->selectedDate,
                    ],
                    [
                        'institution_id' => $institutionId,
                        'tanggal_selesai'=> $this->selectedDate,
                        'catatan'        => 'Udzur syar\'i (Halangan / Haid) dicatat Guru Piket: ' . ($currentUser?->name ?? 'Petugas'),
                        'recorded_by'    => $currentUser?->id ?? 1,
                    ]
                );
            }
        }

        Notification::make()
            ->title("✅ Presensi Siswa Berhasil Disimpan")
            ->body("{$savedCount} data baru tersimpan, {$updatedCount} data diperbarui.")
            ->success()
            ->send();

        $this->loadAttendance();
    }

    /**
     * Tandai semua siswa hadir sekaligus
     */
    public function tandaiSemuaHadir(): void
    {
        foreach ($this->attendanceData as $studentId => $status) {
            // Jangan override siswa yang sudah tercatat sakit, izin, alpa, atau halangan
            if (!in_array($status, ['sakit', 'izin', 'alpa', 'halangan'])) {
                $this->attendanceData[$studentId] = 'hadir';
            }
        }
    }

    public function getClassrooms()
    {
        $user = Auth::user();
        $query = Classroom::orderBy('name');

        if ($user && $user->institution_id && !$user->hasRole('superadmin') && $user->institution_id != 1) {
            $query->where('institution_id', $user->institution_id);
        }

        return $query->get();
    }

    public function getStudents()
    {
        if (!$this->selectedClassroomId) {
            return collect();
        }

        return Student::where('classroom_id', $this->selectedClassroomId)
            ->orderBy('jenis_kelamin') // L dulu baru P
            ->orderBy('name')
            ->get();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $students = $this->getStudents();

        $countFingerprint = 0;
        $countManual = 0;
        $countBelum = 0;

        foreach ($students as $st) {
            $m = $this->attendanceMeta[$st->id] ?? null;
            if ($m && $m['metode'] === 'fingerprint') {
                $countFingerprint++;
            } elseif ($m && $m['metode'] === 'manual') {
                $countManual++;
            } else {
                $countBelum++;
            }
        }

        return view($this::$view, [
            'classrooms'        => $this->getClassrooms(),
            'students'          => $students,
            'countFingerprint'  => $countFingerprint,
            'countManual'       => $countManual,
            'countBelum'        => $countBelum,
        ]);
    }
}
