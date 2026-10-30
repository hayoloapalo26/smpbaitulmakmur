<?php

namespace App\Filament\Staff\Pages;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Student;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PrayerAttendanceReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string $view = 'filament.staff.pages.prayer-attendance-report';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $navigationLabel = 'Rekap Absensi & Sholat Siswa';
    protected static ?string $title = 'Rekap Absensi Harian & Sholat Siswa';
    protected static ?int $navigationSort = 5;

    public ?int $selectedInstitutionId = null;
    public ?int $selectedClassroomId = null;
    public string $selectedPeriod = 'semua'; // semua | bulan_ini | hari_ini
    public string $search = '';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('waka_kesiswaan') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole([
            'waka_kesiswaan', 'kepala_sekolah', 'wali_kelas', 'guru_kelas', 'tata_usaha', 'superadmin',
        ]);
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1) {
            $this->selectedInstitutionId = $user->institution_id;
        }
    }

    public function updatedSelectedInstitutionId(): void
    {
        $this->selectedClassroomId = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('download_pdf')
                ->label('Unduh PDF Rekap Absensi')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('report.prayer', [
                    'institution_id' => $this->selectedInstitutionId,
                    'classroom_id'   => $this->selectedClassroomId,
                    'period'         => $this->selectedPeriod,
                ]))
                ->openUrlInNewTab(),
        ];
    }

    public function getInstitutionsProperty()
    {
        return Institution::where('id', '!=', 1)->orderBy('id')->get();
    }

    public function getClassroomsProperty()
    {
        $user = Auth::user();
        $query = Classroom::withoutGlobalScopes()->orderBy('name');

        $instId = (!$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1)
            ? $user->institution_id
            : $this->selectedInstitutionId;

        if ($instId) {
            $query->where('institution_id', $instId);
        }

        if (
            $user->hasAnyRole(['wali_kelas', 'guru_kelas']) &&
            !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'tata_usaha', 'superadmin'])
        ) {
            $query->reguler()->where('wali_kelas_id', $user->id);
        }

        return $query->get();
    }

    public function getReportDataProperty(): array
    {
        $user = Auth::user();
        $instId = (!$user->hasRole('superadmin') && $user->institution_id && $user->institution_id != 1)
            ? $user->institution_id
            : $this->selectedInstitutionId;

        $studentsQuery = Student::withoutGlobalScopes()
            ->with(['classroom', 'institution', 'dailyAttendances', 'prayerAttendances', 'subjectAttendances.teachingJournal'])
            ->orderBy('name');

        if ($instId) {
            $studentsQuery->where('institution_id', $instId);
        }

        if (
            $user->hasAnyRole(['wali_kelas', 'guru_kelas']) &&
            !$user->hasAnyRole(['waka_kesiswaan', 'kepala_sekolah', 'tata_usaha', 'superadmin'])
        ) {
            $waliClassIds = Classroom::withoutGlobalScopes()->reguler()->where('wali_kelas_id', $user->id)->pluck('id')->toArray();
            $studentsQuery->whereIn('classroom_id', $waliClassIds);
        }

        if ($this->selectedClassroomId) {
            $studentsQuery->where('classroom_id', $this->selectedClassroomId);
        }

        if (!empty(trim($this->search))) {
            $s = trim($this->search);
            $studentsQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('nis', 'like', "%{$s}%");
            });
        }

        $students = $studentsQuery->get();
        $today = Carbon::today()->toDateString();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $rows = [];
        $summary = [
            'total_siswa'        => $students->count(),
            'total_hadir'        => 0,
            'total_sakit'        => 0,
            'total_izin'         => 0,
            'total_alpa'         => 0,
            'total_sholat_hadir' => 0,
            'total_sholat_alpa'  => 0,
        ];

        foreach ($students as $student) {
            $daily = $student->dailyAttendances->filter(function ($d) use ($today, $currentMonth, $currentYear) {
                if (($d->tipe_absen ?? 'masuk') !== 'masuk') {
                    return false;
                }
                if ($this->selectedPeriod === 'hari_ini') {
                    return Carbon::parse($d->tanggal)->toDateString() === $today;
                }
                if ($this->selectedPeriod === 'bulan_ini') {
                    $dt = Carbon::parse($d->tanggal);
                    return $dt->month === $currentMonth && $dt->year === $currentYear;
                }
                return true;
            });

            $prayers = $student->prayerAttendances->filter(function ($p) use ($today, $currentMonth, $currentYear) {
                if ($this->selectedPeriod === 'hari_ini') {
                    return Carbon::parse($p->tanggal)->toDateString() === $today;
                }
                if ($this->selectedPeriod === 'bulan_ini') {
                    $dt = Carbon::parse($p->tanggal);
                    return $dt->month === $currentMonth && $dt->year === $currentYear;
                }
                return true;
            });

            $hadirMasuk = $daily->where('status', 'hadir')->count();
            $terlambat  = $daily->where('status', 'hadir')->where('is_late', true)->count();
            $sakit      = $daily->where('status', 'sakit')->count();
            $izin       = $daily->where('status', 'izin')->count();
            $alpa       = $daily->where('status', 'alpa')->count();

            $sholatHadir     = $prayers->where('status', 'hadir')->count();
            $sholatSakitIzin = $prayers->whereIn('status', ['sakit', 'izin', 'halangan'])->count();
            $sholatAlpa      = $prayers->where('status', 'alpa')->count();
            $tdkKhusyu       = $prayers->where('status', 'hadir')->where('is_khusyu', false)->count();

            $summary['total_hadir']        += $hadirMasuk;
            $summary['total_sakit']        += $sakit;
            $summary['total_izin']         += $izin;
            $summary['total_alpa']         += $alpa;
            $summary['total_sholat_hadir'] += $sholatHadir;
            $summary['total_sholat_alpa']  += $sholatAlpa;

            $rows[] = [
                'id'                => $student->id,
                'nis'               => $student->nis ?? '-',
                'name'              => $student->name,
                'classroom'         => $student->classroom->name ?? '-',
                'institution'       => $student->institution->name ?? '-',
                'hadir_masuk'       => $hadirMasuk,
                'terlambat'         => $terlambat,
                'sakit'             => $sakit,
                'izin'              => $izin,
                'alpa'              => $alpa,
                'sholat_hadir'      => $sholatHadir,
                'sholat_sakit_izin' => $sholatSakitIzin,
                'sholat_alpa'       => $sholatAlpa,
                'tdk_khusyu'        => $tdkKhusyu,
            ];
        }

        $instName = $instId
            ? (Institution::withoutGlobalScopes()->where('id', $instId)->value('name') ?? 'Lembaga')
            : 'Semua Lembaga';

        return [
            'institution_name' => $instName,
            'summary'          => $summary,
            'rows'             => $rows,
        ];
    }
}
