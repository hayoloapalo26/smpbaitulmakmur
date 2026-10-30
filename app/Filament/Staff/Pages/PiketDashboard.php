<?php

namespace App\Filament\Staff\Pages;

use App\Models\AttributeViolation;
use App\Models\DailyAttendance;
use App\Models\DutySchedule;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PiketDashboard extends Page
{
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin'])) {
            return true;
        }

        return $user->isPiketToday();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static string $view = 'filament.staff.pages.piket-dashboard';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Dasbor Piket Harian';
    protected static ?string $title = 'Dasbor Guru Piket Harian';
    protected static ?int $navigationSort = 1;

    public string $hariIni = '';
    public string $tanggalFormatted = '';
    public bool $isCurrentUserPiket = false;
    public array $petugasHariIni = [];

    // Statistik
    public int $totalSiswa = 0;
    public int $siswaHadir = 0;
    public int $siswaTerlambat = 0;
    public int $siswaIzin = 0;
    public int $siswaSakit = 0;
    public int $siswaAlpa = 0;
    public float $persenKehadiran = 0.0;

    // Data lists
    public $guruIzinHariIni = [];
    public $siswaBermasalahHariIni = [];
    public $pelanggaranAtributHariIni = [];

    public function mount(): void
    {
        $today = Carbon::today();
        $this->hariIni = Schedule::getIndonesianDayName($today);
        $this->tanggalFormatted = $today->translatedFormat('l, d F Y');

        $user = Auth::user();
        $this->isCurrentUserPiket = $user?->isPiketToday() ?? false;

        $instId = $user?->institution_id;
        $isSuperadmin = $user?->hasRole('superadmin') ?? false;

        // 1. Petugas Piket Hari Ini (Membaca otomatis berdasarkan hari atau tanggal)
        $scheduleQuery = DutySchedule::where(function ($q) use ($today) {
            $q->where('hari', $this->hariIni)
              ->orWhereDate('tanggal', $today);
        })->with('teacher');
        if ($instId && $instId != 1 && !$isSuperadmin) {
            $scheduleQuery->where(function ($q) use ($instId) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('duty_schedules', 'institution_id')) {
                    $q->where('institution_id', $instId);
                }
            });
        }
        $this->petugasHariIni = $scheduleQuery->get()
            ->pluck('teacher.name')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // 2. Statistik Kehadiran Siswa
        $studentQuery = Student::query();
        if ($instId && $instId != 1 && !$isSuperadmin) {
            $studentQuery->where('institution_id', $instId);
        }
        $this->totalSiswa = $studentQuery->count();

        $dailyAttQuery = DailyAttendance::whereDate('tanggal', $today);
        if ($instId && $instId != 1 && !$isSuperadmin) {
            $dailyAttQuery->whereHas('student', fn ($q) => $q->where('institution_id', $instId));
        }

        $this->siswaHadir = (clone $dailyAttQuery)->where('status', 'hadir')->count();
        $this->siswaTerlambat = (clone $dailyAttQuery)->where('is_late', true)->count();
        $this->siswaIzin = (clone $dailyAttQuery)->where('status', 'izin')->count();
        $this->siswaSakit = (clone $dailyAttQuery)->where('status', 'sakit')->count();
        $this->siswaAlpa = (clone $dailyAttQuery)->where('status', 'alpa')->count();

        if ($this->totalSiswa > 0) {
            $this->persenKehadiran = round(($this->siswaHadir / $this->totalSiswa) * 100, 1);
        }

        // 3. Guru Berhalangan / Izin Hari Ini (Inval Monitor)
        $teacherAttQuery = TeacherAttendance::whereDate('tanggal', $today)
            ->whereIn('status', ['izin', 'sakit', 'alpa', 'dinas_luar'])
            ->with(['teacher', 'substituteTeacher', 'schedule.classroom', 'schedule.subject', 'classroom', 'subject']);

        if ($instId && $instId != 1 && !$isSuperadmin) {
            $teacherAttQuery->where(function ($q) use ($instId) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('teacher_attendances', 'institution_id')) {
                    $q->where('institution_id', $instId);
                }
            });
        }
        $this->guruIzinHariIni = $teacherAttQuery->get();

        // 4. Siswa Terlambat / Tidak Hadir Hari Ini
        $issueQuery = DailyAttendance::whereDate('tanggal', $today)
            ->where(function ($q) {
                $q->where('is_late', true)
                  ->orWhereIn('status', ['alpa', 'izin', 'sakit']);
            })
            ->with('student.classroom');

        if ($instId && $instId != 1 && !$isSuperadmin) {
            $issueQuery->whereHas('student', fn ($q) => $q->where('institution_id', $instId));
        }
        $this->siswaBermasalahHariIni = $issueQuery->latest()->take(15)->get();

        // 5. Pelanggaran Atribut Hari Ini
        $violationQuery = AttributeViolation::whereDate('tanggal', $today)
            ->with(['student.classroom', 'teacher']);

        if ($instId && $instId != 1 && !$isSuperadmin) {
            $violationQuery->whereHas('student', fn ($q) => $q->where('institution_id', $instId));
        }
        $this->pelanggaranAtributHariIni = $violationQuery->latest()->take(10)->get();
    }
}
