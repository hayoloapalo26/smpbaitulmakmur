<?php

namespace App\Filament\Staff\Resources\PresensiIzinSiswaResource\Widgets;

use App\Models\DailyAttendance;
use App\Models\Student;
use App\Models\StudentHalangan;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class DailyAttendanceStatsOverviewWidget extends Widget
{
    protected static string $view = 'filament.staff.resources.presensi-izin-siswa.daily-stats-widget';

    protected int | string | array $columnSpan = 'full';

    public string $mode = 'harian'; // 'harian' atau 'rentang'
    public string $tanggal = '';
    public string $tanggalMulai = '';
    public string $tanggalSelesai = '';
    public ?int $classroomId = null;
    public bool $isWakaOrAdmin = false;

    public array $stats = [];

    public function mount(?string $tanggal = null, ?int $classroomId = null): void
    {
        $user = Auth::user();
        $this->isWakaOrAdmin = (bool) ($user && $user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'superadmin']));
        $this->tanggal = $tanggal ?: Carbon::today()->toDateString();
        $this->tanggalMulai = Carbon::today()->startOfMonth()->toDateString();
        $this->tanggalSelesai = Carbon::today()->toDateString();
        $this->classroomId = $classroomId;
        $this->calculateStats();
    }

    #[On('date-changed')]
    #[On('refresh-presensi-table')]
    #[On('fingerspot-synced')]
    public function updateDate(?string $newDate = null): void
    {
        if ($newDate) {
            $this->tanggal = $newDate;
        }
        $this->calculateStats();
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
        $this->dispatch('mode-changed', mode: $this->mode);

        if ($this->mode === 'rentang') {
            $this->dispatch('date-range-changed', startDate: $this->tanggalMulai, endDate: $this->tanggalSelesai);
        } else {
            $this->dispatch('date-changed', newDate: $this->tanggal);
        }

        $this->calculateStats();
    }

    public function setToday(): void
    {
        $this->tanggal = Carbon::today()->toDateString();
        $this->dispatch('date-changed', newDate: $this->tanggal);
        $this->calculateStats();
    }

    public function setYesterday(): void
    {
        $this->tanggal = Carbon::parse($this->tanggal)->subDay()->toDateString();
        $this->dispatch('date-changed', newDate: $this->tanggal);
        $this->calculateStats();
    }

    public function setTomorrow(): void
    {
        $this->tanggal = Carbon::parse($this->tanggal)->addDay()->toDateString();
        $this->dispatch('date-changed', newDate: $this->tanggal);
        $this->calculateStats();
    }

    public function updatedTanggal(): void
    {
        $this->dispatch('date-changed', newDate: $this->tanggal);
        $this->calculateStats();
    }

    public function updatedTanggalMulai(): void
    {
        $this->dispatch('date-range-changed', startDate: $this->tanggalMulai, endDate: $this->tanggalSelesai);
        $this->calculateStats();
    }

    public function updatedTanggalSelesai(): void
    {
        $this->dispatch('date-range-changed', startDate: $this->tanggalMulai, endDate: $this->tanggalSelesai);
        $this->calculateStats();
    }

    public function setThisWeek(): void
    {
        $this->tanggalMulai = Carbon::today()->startOfWeek()->toDateString();
        $this->tanggalSelesai = Carbon::today()->toDateString();
        $this->dispatch('date-range-changed', startDate: $this->tanggalMulai, endDate: $this->tanggalSelesai);
        $this->calculateStats();
    }

    public function setThisMonth(): void
    {
        $this->tanggalMulai = Carbon::today()->startOfMonth()->toDateString();
        $this->tanggalSelesai = Carbon::today()->toDateString();
        $this->dispatch('date-range-changed', startDate: $this->tanggalMulai, endDate: $this->tanggalSelesai);
        $this->calculateStats();
    }

    public function setLastMonth(): void
    {
        $this->tanggalMulai = Carbon::today()->subMonth()->startOfMonth()->toDateString();
        $this->tanggalSelesai = Carbon::today()->subMonth()->endOfMonth()->toDateString();
        $this->dispatch('date-range-changed', startDate: $this->tanggalMulai, endDate: $this->tanggalSelesai);
        $this->calculateStats();
    }

    public function calculateStats(): void
    {
        $user = Auth::user();

        $studentQuery = Student::withoutGlobalScopes()->where('status_siswa', 'aktif');
        if ($user && !$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
            $studentQuery->where('institution_id', $user->institution_id);
        }
        if ($this->classroomId) {
            $studentQuery->where('classroom_id', $this->classroomId);
        }

        $totalSiswa = (clone $studentQuery)->count();
        $studentIds = (clone $studentQuery)->pluck('id')->toArray();

        $attQuery = DailyAttendance::withoutGlobalScopes()->whereIn('student_id', $studentIds);

        if ($this->mode === 'rentang') {
            $startDate = $this->tanggalMulai ?: Carbon::today()->startOfMonth()->toDateString();
            $endDate = $this->tanggalSelesai ?: Carbon::today()->toDateString();
            $attQuery->whereBetween('tanggal', [$startDate, $endDate]);
            $dateLabel = Carbon::parse($startDate)->translatedFormat('d M Y') . ' s.d ' . Carbon::parse($endDate)->translatedFormat('d M Y');
        } else {
            $date = $this->tanggal ?: Carbon::today()->toDateString();
            $attQuery->whereDate('tanggal', $date);
            $dateLabel = Carbon::parse($date)->translatedFormat('l, d F Y');
        }

        $attendances = $attQuery->get();

        // 1. Kedatangan Masuk
        $masukAtts = $attendances->where('tipe_absen', 'masuk');
        $masukHadir = $masukAtts->where('status', 'hadir')->count();
        $masukTepat = $masukAtts->where('status', 'hadir')->where('is_late', false)->count();
        $masukTerlambat = $masukAtts->where('status', 'hadir')->where('is_late', true)->count();
        $masukBelum = $this->mode === 'rentang'
            ? '-'
            : max(0, $totalSiswa - $masukHadir - $attendances->whereIn('status', ['sakit', 'izin', 'alpa'])->unique('student_id')->count());

        // 2. Sholat Dhuhur
        $dhuhurAtts = $attendances->where('tipe_absen', 'sholat_dhuhur');
        $dhuhurHadir = $dhuhurAtts->where('status', 'hadir')->count();
        $dhuhurTepat = $dhuhurAtts->where('status', 'hadir')->where('is_late', false)->count();
        $dhuhurTerlambat = $dhuhurAtts->where('status', 'hadir')->where('is_late', true)->count();
        $dhuhurHalangan = $dhuhurAtts->where('status', 'halangan')->count();

        // 3. Sholat Ashar
        $asharAtts = $attendances->filter(fn ($a) => in_array($a->tipe_absen, ['sholat_ashar', 'pulang']));
        $asharHadir = $asharAtts->where('status', 'hadir')->count();
        $asharTepat = $asharAtts->where('status', 'hadir')->where('is_late', false)->count();
        $asharTerlambat = $asharAtts->where('status', 'hadir')->where('is_late', true)->count();
        $asharHalangan = $asharAtts->where('status', 'halangan')->count();

        // 4. Izin / Sakit / Alpa
        $sakitCount = $this->mode === 'rentang'
            ? $attendances->where('status', 'sakit')->where('tipe_absen', 'masuk')->count()
            : $attendances->where('status', 'sakit')->unique('student_id')->count();

        $izinCount = $this->mode === 'rentang'
            ? $attendances->where('status', 'izin')->where('tipe_absen', 'masuk')->count()
            : $attendances->where('status', 'izin')->unique('student_id')->count();

        $alpaCount = $this->mode === 'rentang'
            ? $attendances->where('status', 'alpa')->where('tipe_absen', 'masuk')->count()
            : $attendances->where('status', 'alpa')->unique('student_id')->count();

        $this->stats = [
            'mode'            => $this->mode,
            'date_formatted'  => $dateLabel,
            'total_siswa'     => $totalSiswa,
            'masuk_hadir'     => $masukHadir,
            'masuk_tepat'     => $masukTepat,
            'masuk_terlambat' => $masukTerlambat,
            'masuk_belum'     => $masukBelum,
            'dhuhur_hadir'    => $dhuhurHadir,
            'dhuhur_tepat'    => $dhuhurTepat,
            'dhuhur_terlambat'=> $dhuhurTerlambat,
            'dhuhur_halangan' => $dhuhurHalangan,
            'ashar_hadir'     => $asharHadir,
            'ashar_tepat'     => $asharTepat,
            'ashar_terlambat' => $asharTerlambat,
            'ashar_halangan'  => $asharHalangan,
            'sakit_count'     => $sakitCount,
            'izin_count'      => $izinCount,
            'alpa_count'      => $alpaCount,
        ];
    }
}
