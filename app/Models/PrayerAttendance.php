<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class PrayerAttendance extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    public static bool $isSyncing = false;

    protected static function booted(): void
    {
        static::saving(function (PrayerAttendance $prayer) {
            if (empty($prayer->teacher_id)) {
                $prayer->teacher_id = auth()->id() ?? 1;
            }
            if (empty($prayer->jenis_sholat)) {
                $prayer->jenis_sholat = 'Dhuhur';
            }
        });

        static::saved(function (PrayerAttendance $prayer) {
            if (self::$isSyncing) {
                return;
            }

            self::$isSyncing = true;
            try {
                // Sinkronkan juga ke DailyAttendance agar rekap presensi harian & sholat selalu konsisten
                $tipeAbsen = 'sholat_' . strtolower($prayer->jenis_sholat);

                DailyAttendance::$isSyncing = true;
                try {
                    DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                        [
                            'student_id' => $prayer->student_id,
                            'tanggal'    => Carbon::parse($prayer->tanggal)->toDateString(),
                            'tipe_absen' => $tipeAbsen,
                        ],
                        [
                            'institution_id' => $prayer->institution_id,
                            'teacher_id'     => $prayer->teacher_id ?: (auth()->id() ?? 1),
                            'status'         => $prayer->status,
                            'metode_absen'   => 'manual',
                            'is_late'        => false,
                            'keterangan'     => $prayer->is_khusyu ? "Sholat {$prayer->jenis_sholat} (Khusyu)" : "Sholat {$prayer->jenis_sholat} (Kurang Khusyu)",
                        ]
                    );
                } finally {
                    DailyAttendance::$isSyncing = false;
                }

                // Informasikan ke Waka Kesiswaan di lembaga siswa terkait jika sakit/izin/alpa atau tidak khusyu
                if (
                    (in_array($prayer->status, ['sakit', 'izin', 'alpa']) || !$prayer->is_khusyu) &&
                    ($prayer->wasRecentlyCreated || $prayer->wasChanged(['status', 'is_khusyu']))
                ) {
                    $prayer->notifyWakaKesiswaan();
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
        $khusyuNote = !$this->is_khusyu ? ' (Catatan: Tidak Khusyu)' : '';

        foreach ($wakaUsers as $waka) {
            Notification::make()
                ->title("Presensi Sholat {$this->jenis_sholat}: {$student->name}")
                ->body("Siswa {$student->name} (Kelas {$kelasName}) berstatus {$statusLabel}{$khusyuNote} pada {$tglFormatted}.")
                ->color($this->status === 'alpa' || !$this->is_khusyu ? 'danger' : 'warning')
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
