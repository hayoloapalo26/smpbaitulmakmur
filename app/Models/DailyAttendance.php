<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class DailyAttendance extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'is_late'              => 'boolean',
        'late_penalty_points'  => 'integer',
        'late_approved_at'     => 'datetime',
    ];

    public static bool $isSyncing = false;

    protected static function booted(): void
    {
        static::saving(function (DailyAttendance $attendance) {
            if (array_key_exists('catatan', $attendance->getAttributes())) {
                if (empty($attendance->keterangan)) {
                    $attendance->keterangan = $attendance->getAttribute('catatan');
                }
                unset($attendance->catatan);
            }

            if (array_key_exists('waktu_kepulangan', $attendance->getAttributes())) {
                unset($attendance->waktu_kepulangan);
            }

            if (empty($attendance->teacher_id)) {
                $attendance->teacher_id = auth()->id() ?? 1;
            }

            if (empty($attendance->tipe_absen)) {
                $attendance->tipe_absen = 'masuk';
            }

            // Otomatisasi Keterlambatan Masuk Sekolah (Dimulai 06.46 WIB, pukul 06.45 tepat waktu/tidak terlambat):
            // Default disiapkan poin 5 dan status 'pending' (menunggu ACC Waka Kesiswaan sebelum poin difixkan).
            if ($attendance->tipe_absen === 'masuk' && $attendance->status === 'hadir') {
                $waktu = $attendance->waktu_kedatangan ? Carbon::parse($attendance->waktu_kedatangan)->format('H:i:s') : null;
                $isLate = $waktu ? ($waktu >= '06:46:00') : (bool) $attendance->is_late;

                if ($isLate) {
                    $attendance->is_late = true;
                    if (empty($attendance->late_penalty_points)) {
                        $attendance->late_penalty_points = 5;
                    }
                    if (empty($attendance->late_approval_status)) {
                        $attendance->late_approval_status = 'pending';
                    }
                } else {
                    $attendance->is_late = false;
                    $attendance->late_penalty_points = null;
                    $attendance->late_approval_status = null;
                }
            }
        });

        static::saved(function (DailyAttendance $attendance) {
            if (self::$isSyncing) {
                return;
            }

            self::$isSyncing = true;
            try {
                // 1. Jika ini absensi sholat (dari Scanner / Fingerspot Revo W230N / Hikvision / Absensi Kolektif), sinkronkan ke PrayerAttendance
                $sholatMap = [
                    'sholat_dhuha'  => 'Dhuha',
                    'sholat_dhuhur' => 'Dhuhur',
                    'sholat_ashar'  => 'Ashar',
                ];

                if (isset($sholatMap[$attendance->tipe_absen])) {
                    PrayerAttendance::$isSyncing = true;
                    try {
                        PrayerAttendance::withoutGlobalScopes()->updateOrCreate(
                            [
                                'student_id'   => $attendance->student_id,
                                'tanggal'      => Carbon::parse($attendance->tanggal)->toDateString(),
                                'jenis_sholat' => $sholatMap[$attendance->tipe_absen],
                            ],
                            [
                                'institution_id' => $attendance->institution_id,
                                'teacher_id'     => $attendance->teacher_id ?: (auth()->id() ?? 1),
                                'status'         => $attendance->status,
                                'is_khusyu'      => true,
                            ]
                        );
                    } finally {
                        PrayerAttendance::$isSyncing = false;
                    }
                }

                // 2. Jika siswa diinput Sakit, Izin, atau Alpha (misal saat presensi pagi oleh Guru Piket),
                // otomatis sistem 1 hari penuh merekap absensi sholatnya (Dhuhur & Ashar) dengan status yang sama
                if (in_array($attendance->status, ['sakit', 'izin', 'alpa'])) {
                    $tanggalStr = Carbon::parse($attendance->tanggal)->toDateString();
                    $prayersToSync = ['Dhuhur', 'Ashar'];

                    foreach ($prayersToSync as $prayerName) {
                        PrayerAttendance::$isSyncing = true;
                        try {
                            PrayerAttendance::withoutGlobalScopes()->updateOrCreate(
                                [
                                    'student_id'   => $attendance->student_id,
                                    'tanggal'      => $tanggalStr,
                                    'jenis_sholat' => $prayerName,
                                ],
                                [
                                    'institution_id' => $attendance->institution_id,
                                    'teacher_id'     => $attendance->teacher_id ?: (auth()->id() ?? 1),
                                    'status'         => $attendance->status,
                                    'is_khusyu'      => true,
                                ]
                            );
                        } finally {
                            PrayerAttendance::$isSyncing = false;
                        }

                        $tipeSholatKey = 'sholat_' . strtolower($prayerName);
                        DailyAttendance::$isSyncing = true;
                        try {
                            DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                [
                                    'student_id' => $attendance->student_id,
                                    'tanggal'    => $tanggalStr,
                                    'tipe_absen' => $tipeSholatKey,
                                ],
                                [
                                    'institution_id' => $attendance->institution_id,
                                    'teacher_id'     => $attendance->teacher_id ?: (auth()->id() ?? 1),
                                    'status'         => $attendance->status,
                                    'metode_absen'   => $attendance->metode_absen ?? 'manual',
                                    'is_late'        => false,
                                    'keterangan'     => $attendance->keterangan ?: "Otomatis 1 hari ({$attendance->status}) dari input presensi piket",
                                ]
                            );
                        } finally {
                            DailyAttendance::$isSyncing = false;
                        }
                    }
                }

                // 3. Jika siswi diinput Halangan / Haid (Udzur Syar'i),
                // otomatis sistem 1 hari merekap absensi sholatnya (Dhuhur & Ashar) sebagai Halangan
                if ($attendance->status === 'halangan') {
                    $tanggalStr = Carbon::parse($attendance->tanggal)->toDateString();
                    $prayersToSync = ['Dhuhur', 'Ashar'];

                    foreach ($prayersToSync as $prayerName) {
                        PrayerAttendance::$isSyncing = true;
                        try {
                            PrayerAttendance::withoutGlobalScopes()->updateOrCreate(
                                [
                                    'student_id'   => $attendance->student_id,
                                    'tanggal'      => $tanggalStr,
                                    'jenis_sholat' => $prayerName,
                                ],
                                [
                                    'institution_id' => $attendance->institution_id,
                                    'teacher_id'     => $attendance->teacher_id ?: (auth()->id() ?? 1),
                                    'status'         => 'halangan',
                                    'is_khusyu'      => true,
                                ]
                            );
                        } finally {
                            PrayerAttendance::$isSyncing = false;
                        }

                        $tipeSholatKey = 'sholat_' . strtolower($prayerName);
                        DailyAttendance::$isSyncing = true;
                        try {
                            DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                                [
                                    'student_id' => $attendance->student_id,
                                    'tanggal'    => $tanggalStr,
                                    'tipe_absen' => $tipeSholatKey,
                                ],
                                [
                                    'institution_id' => $attendance->institution_id,
                                    'teacher_id'     => $attendance->teacher_id ?: (auth()->id() ?? 1),
                                    'status'         => 'halangan',
                                    'metode_absen'   => 'manual',
                                    'is_late'        => false,
                                    'keterangan'     => $attendance->keterangan ?: "Udzur syar'i (Halangan / Haid)",
                                ]
                            );
                        } finally {
                            DailyAttendance::$isSyncing = false;
                        }
                    }
                }

                // 4. Jika siswa Sakit, Izin, atau Alpha, informasikan ke Waka Kesiswaan di lembaga siswa tersebut
                if (
                    in_array($attendance->status, ['sakit', 'izin', 'alpa']) &&
                    ($attendance->wasRecentlyCreated || $attendance->wasChanged('status'))
                ) {
                    $attendance->notifyWakaKesiswaan();
                }

                // 5. Jika siswa terlambat masuk dan statusnya pending, informasikan ke Waka Kesiswaan untuk di-ACC
                if (
                    $attendance->tipe_absen === 'masuk' &&
                    $attendance->is_late &&
                    $attendance->late_approval_status === 'pending' &&
                    ($attendance->wasRecentlyCreated || $attendance->wasChanged('is_late') || $attendance->wasChanged('late_approval_status'))
                ) {
                    $attendance->notifyWakaKesiswaanLate();
                }
            } finally {
                self::$isSyncing = false;
            }
        });
    }

    public function notifyWakaKesiswaan(): void
    {
        $student = Student::withoutGlobalScopes()->with('classroom')->find($this->student_id);
        if (!$student || !$student->institution_id) {
            return;
        }

        $wakaUsers = User::withoutGlobalScopes()
            ->where('institution_id', $student->institution_id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'waka_kesiswaan'))
            ->get();

        if ($wakaUsers->isEmpty()) {
            return;
        }

        $statusLabel = strtoupper($this->status);
        $kelasName = $student->classroom->name ?? '-';
        $tglFormatted = Carbon::parse($this->tanggal)->translatedFormat('d M Y');
        $tipeLabel = str_starts_with((string) $this->tipe_absen, 'sholat_')
            ? 'Absensi ' . ucwords(str_replace('_', ' ', $this->tipe_absen))
            : 'Absensi Harian';

        foreach ($wakaUsers as $waka) {
            Notification::make()
                ->title("{$tipeLabel}: {$student->name} ({$statusLabel})")
                ->body("Siswa {$student->name} (Kelas {$kelasName}) tercatat {$statusLabel} pada {$tglFormatted}." . ($this->keterangan ? " Ket: {$this->keterangan}" : ''))
                ->color(match ($this->status) {
                    'alpa'  => 'danger',
                    'sakit' => 'warning',
                    default => 'info',
                })
                ->actions([
                    NotificationAction::make('lihat_rekap')
                        ->label('Lihat Rekap Absensi')
                        ->url('/staff/prayer-attendance-report'),
                ])
                ->sendToDatabase($waka);
        }
    }

    public function notifyWakaKesiswaanLate(): void
    {
        $student = $this->student ?: Student::withoutGlobalScopes()->with('classroom')->find($this->student_id);
        if (!$student) {
            return;
        }

        $wakaQuery = User::withoutGlobalScopes()
            ->whereHas('roles', fn ($q) => $q->where('name', 'waka_kesiswaan'));

        if ($student->institution_id && $student->institution_id != 1) {
            $wakaQuery->where(function ($q) use ($student) {
                $q->where('institution_id', $student->institution_id)
                  ->orWhere('institution_id', 1);
            });
        }

        $wakaUsers = $wakaQuery->get();

        if ($wakaUsers->isEmpty()) {
            return;
        }

        $kelasName = $student->classroom->name ?? '-';
        $jam = $this->waktu_kedatangan ? Carbon::parse($this->waktu_kedatangan)->format('H:i') : '06:46';
        $tglFormatted = Carbon::parse($this->tanggal)->translatedFormat('d M Y');

        foreach ($wakaUsers as $waka) {
            Notification::make()
                ->title("⏰ Siswa Terlambat: {$student->name} (Kelas {$kelasName})")
                ->body("Siswa {$student->name} terlambat masuk ({$jam} WIB) pada {$tglFormatted}. Poin keterlambatan (+5 Poin) menunggu persetujuan (ACC) Anda.")
                ->warning()
                ->actions([
                    NotificationAction::make('tinjau_acc')
                        ->label('Tinjau & ACC Keterlambatan')
                        ->url('/staff/presensi-izin-siswas?activeTab=terlambat'),
                ])
                ->sendToDatabase($waka);
        }
    }

    /**
     * ACC / Setujui Poin Keterlambatan (+5 Poin) oleh Waka Kesiswaan.
     */
    public function approveLatePenalty(?int $approvedById = null): bool
    {
        if (!$this->is_late || $this->tipe_absen !== 'masuk') {
            return false;
        }

        $approvedById = $approvedById ?: (auth()->id() ?? 1);
        $approver = User::find($approvedById);
        $approverName = $approver ? $approver->name : 'Waka Kesiswaan';
        $points = $this->late_penalty_points ?: 5;
        $student = $this->student ?: Student::withoutGlobalScopes()->find($this->student_id);

        $violationLog = null;
        if ($this->late_violation_log_id) {
            $violationLog = StudentViolationLog::find($this->late_violation_log_id);
        }

        $jam = $this->waktu_kedatangan ? Carbon::parse($this->waktu_kedatangan)->format('H:i') : '06:46';

        if (!$violationLog) {
            $violationLog = StudentViolationLog::create([
                'student_id'             => $this->student_id,
                'classroom_id'           => $student?->classroom_id,
                'tanggal'                => $this->tanggal,
                'jam'                    => $jam,
                'bentuk_pelanggaran'     => 'Keterlambatan Masuk Sekolah (Mulai 06.46 WIB)',
                'poin'                   => $points,
                'teacher_id'             => $approvedById,
                'tindak_lanjut_langsung' => "Poin keterlambatan (+{$points} Poin) disetujui (ACC) oleh Waka Kesiswaan ({$approverName}).",
                'acc_piket'              => true,
                'acc_piket_by'           => $approvedById,
                'acc_piket_at'           => now(),
                'institution_id'         => $this->institution_id ?: $student?->institution_id,
            ]);
        } else {
            $violationLog->update([
                'poin'                   => $points,
                'teacher_id'             => $approvedById,
                'tindak_lanjut_langsung' => "Poin keterlambatan (+{$points} Poin) disetujui (ACC) oleh Waka Kesiswaan ({$approverName}).",
                'acc_piket'              => true,
                'acc_piket_by'           => $approvedById,
                'acc_piket_at'           => now(),
            ]);
        }

        $this->updateQuietly([
            'late_approval_status'  => 'approved',
            'late_approved_by'      => $approvedById,
            'late_approved_at'      => now(),
            'late_rejection_reason' => null,
            'late_violation_log_id' => $violationLog->id,
        ]);

        return true;
    }

    /**
     * Tolak / Berikan Dispensasi Keterlambatan (0 Poin).
     */
    public function rejectLatePenalty(string $reason = 'Dispensasi oleh Waka Kesiswaan', ?int $rejectedById = null): bool
    {
        if (!$this->is_late || $this->tipe_absen !== 'masuk') {
            return false;
        }

        $rejectedById = $rejectedById ?: (auth()->id() ?? 1);

        // Jika sebelumnya sudah ada log pelanggaran, hapus log dan score log agar skor dikembalikan
        if ($this->late_violation_log_id) {
            $log = StudentViolationLog::find($this->late_violation_log_id);
            if ($log) {
                CharacterScoreLog::where('student_id', $this->student_id)
                    ->where('tanggal', $this->tanggal)
                    ->where('description', 'like', '%Keterlambatan Masuk Sekolah%')
                    ->delete();
                $log->delete();
            }
        }

        $this->updateQuietly([
            'late_approval_status'  => 'rejected',
            'late_approved_by'      => $rejectedById,
            'late_approved_at'      => now(),
            'late_rejection_reason' => $reason,
            'late_violation_log_id' => null,
        ]);

        return true;
    }

    public function lateApprovedBy()
    {
        return $this->belongsTo(User::class, 'late_approved_by');
    }

    public function lateViolationLog()
    {
        return $this->belongsTo(StudentViolationLog::class, 'late_violation_log_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
