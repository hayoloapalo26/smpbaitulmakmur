<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentHalangan extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    protected static function booted(): void
    {
        static::saved(function (StudentHalangan $halangan) {
            $student = $halangan->student;
            if (!$student) {
                return;
            }

            $startDate = \Carbon\Carbon::parse($halangan->tanggal_mulai);
            $endDate = $halangan->tanggal_selesai
                ? \Carbon\Carbon::parse($halangan->tanggal_selesai)
                : $startDate->copy()->addDays(6);

            if ($endDate->diffInDays($startDate) > 14) {
                $endDate = $startDate->copy()->addDays(14);
            }

            $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
            $prayers = ['Dhuhur', 'Ashar'];

            foreach ($period as $date) {
                $dateStr = $date->toDateString();

                foreach ($prayers as $prayerName) {
                    PrayerAttendance::$isSyncing = true;
                    try {
                        PrayerAttendance::withoutGlobalScopes()->updateOrCreate(
                            [
                                'student_id'   => $student->id,
                                'tanggal'      => $dateStr,
                                'jenis_sholat' => $prayerName,
                            ],
                            [
                                'institution_id' => $student->institution_id ?? $halangan->institution_id,
                                'teacher_id'     => $halangan->recorded_by ?: (auth()->id() ?? 1),
                                'status'         => 'halangan',
                                'is_khusyu'      => true,
                            ]
                        );
                    } finally {
                        PrayerAttendance::$isSyncing = false;
                    }

                    $tipeKey = 'sholat_' . strtolower($prayerName);
                    DailyAttendance::$isSyncing = true;
                    try {
                        DailyAttendance::withoutGlobalScopes()->updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'tanggal'    => $dateStr,
                                'tipe_absen' => $tipeKey,
                            ],
                            [
                                'institution_id' => $student->institution_id ?? $halangan->institution_id,
                                'teacher_id'     => $halangan->recorded_by ?: (auth()->id() ?? 1),
                                'status'         => 'halangan',
                                'metode_absen'   => 'manual',
                                'is_late'        => false,
                                'keterangan'     => "Udzur syar'i (Halangan / Haid)",
                            ]
                        );
                    } finally {
                        DailyAttendance::$isSyncing = false;
                    }
                }
            }
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Apakah siswi sedang halangan pada tanggal tertentu?
     */
    public static function isHalangan(int $studentId, $tanggal): bool
    {
        return static::where('student_id', $studentId)
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->whereNull('tanggal_selesai')
                  ->orWhere('tanggal_selesai', '>=', $tanggal);
            })
            ->exists();
    }
}
