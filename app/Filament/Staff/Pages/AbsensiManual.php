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
        if (!$user) {
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
    protected static ?string $navigationGroup   = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel   = 'Input Presensi Manual';
    protected static ?string $title             = 'Input Presensi Kolektif Per Kelas';
    protected static string  $view              = 'filament.staff.pages.absensi-manual';
    protected static ?int    $navigationSort    = 4;

    // Filter state
    public string $selectedDate;
    public string $tipeAbsen = 'masuk';
    public ?int   $selectedClassroomId = null;

    // Attendance state: [student_id => status]
    public array $attendanceData = [];

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
     * Muat data presensi yang sudah ada untuk tampilan default
     */
    public function loadAttendance(): void
    {
        if (!$this->selectedClassroomId) return;

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
        foreach ($students as $student) {
            if ($existingRecords->has($student->id)) {
                $this->attendanceData[$student->id] = $existingRecords[$student->id]->status;
            } else {
                // Cek halangan siswi untuk tipe sholat
                if ($student->jenis_kelamin === 'P'
                    && in_array($this->tipeAbsen, ['sholat_dhuhur', 'sholat_ashar'])
                    && StudentHalangan::isHalangan($student->id, $this->selectedDate)
                ) {
                    $this->attendanceData[$student->id] = 'halangan';
                } else {
                    $this->attendanceData[$student->id] = null; // belum diisi
                }
            }
        }
    }

    /**
     * Simpan semua data presensi manual
     */
    public function simpan(): void
    {
        $savedCount  = 0;
        $updatedCount = 0;
        $now = Carbon::now()->format('H:i:s');
        $currentUser = Auth::user();

        foreach ($this->attendanceData as $studentId => $status) {
            if ($status === null || $status === '') continue;

            $student = Student::find($studentId);
            $institutionId = $student?->classroom?->institution_id ?? ($currentUser?->institution_id ?? 1);

            $existing = DailyAttendance::where('student_id', $studentId)
                ->where('tanggal', $this->selectedDate)
                ->where('tipe_absen', $this->tipeAbsen)
                ->first();

            $data = [
                'student_id'     => $studentId,
                'institution_id' => $institutionId,
                'teacher_id'     => $currentUser?->id ?? 1,
                'tanggal'        => $this->selectedDate,
                'tipe_absen'     => $this->tipeAbsen,
                'metode_absen'   => 'manual',
                'status'         => $status,
                'is_late'        => ($this->tipeAbsen === 'masuk' && $status === 'hadir' && $now > '07:15:00'),
                'keterangan'     => 'Input manual oleh ' . ($currentUser?->name ?? 'Guru Piket'),
            ];

            if ($this->tipeAbsen === 'masuk' && $status === 'hadir') {
                $data['waktu_kedatangan'] = $now;
            }

            if ($existing) {
                $existing->update($data);
                $updatedCount++;
            } else {
                DailyAttendance::create($data);
                $savedCount++;
            }
        }

        Notification::make()
            ->title("✅ Presensi Manual Berhasil Disimpan")
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
            // Jangan override siswi yang sudah ditandai halangan
            if ($status !== 'halangan') {
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
        if (!$this->selectedClassroomId) return collect();
        return Student::where('classroom_id', $this->selectedClassroomId)
            ->orderBy('jenis_kelamin') // L dulu baru P
            ->orderBy('name')
            ->get();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view($this::$view, [
            'classrooms' => $this->getClassrooms(),
            'students'   => $this->getStudents(),
        ]);
    }
}
