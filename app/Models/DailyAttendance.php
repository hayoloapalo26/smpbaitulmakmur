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

                // 2. Jika siswa Sakit, Izin, atau Alpha, informasikan ke Waka Kesiswaan di lembaga siswa tersebut
                if (
                    in_array($attendance->status, ['sakit', 'izin', 'alpa']) &&
                    ($attendance->wasRecentlyCreated || $attendance->wasChanged('status'))
                ) {
                    $attendance->notifyWakaKesiswaan();
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

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
