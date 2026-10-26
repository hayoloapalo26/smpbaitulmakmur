<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLeaveRequest extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $attributes = [
        'status' => 'diajukan',
        'tipe_izin' => 'harian',
    ];

    protected $casts = [
        'jam_ke' => 'array',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'wakakur_approved_at' => 'datetime',
        'kepsek_approved_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(User::class, 'substitute_teacher_id');
    }

    public function wakakurApprover()
    {
        return $this->belongsTo(User::class, 'wakakur_approved_by');
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['disetujui_kepsek', 'disetujui_wakakur', 'disetujui']);
    }

    /**
     * Cek apakah seorang guru memiliki izin yang sudah di-ACC pada tanggal dan (opsional) jam ke tertentu.
     */
    public static function isTeacherOnLeave(int $teacherId, string $date, ?int $jamKe = null): bool
    {
        return static::getActiveLeaveFor($teacherId, $date, $jamKe) !== null;
    }

    /**
     * Ambil data perizinan aktif yang sudah di-ACC untuk guru pada tanggal dan (opsional) jam ke tertentu.
     */
    public static function getActiveLeaveFor(int $teacherId, string $date, ?int $jamKe = null): ?self
    {
        $leaves = static::withoutGlobalScopes()
            ->where('teacher_id', $teacherId)
            ->whereIn('status', ['disetujui_kepsek', 'disetujui_wakakur', 'disetujui'])
            ->whereDate('tanggal_mulai', '<=', $date)
            ->whereDate('tanggal_selesai', '>=', $date)
            ->get();

        foreach ($leaves as $leave) {
            if ($leave->tipe_izin === 'jam_tertentu') {
                if ($jamKe === null) {
                    return $leave;
                }
                
                $hours = is_array($leave->jam_ke) ? $leave->jam_ke : json_decode($leave->jam_ke ?? '[]', true);
                if (!empty($hours) && in_array((int)$jamKe, array_map('intval', $hours))) {
                    return $leave;
                }
                
                if ($leave->jam_ke_mulai !== null && $leave->jam_ke_selesai !== null) {
                    if ((int)$jamKe >= (int)$leave->jam_ke_mulai && (int)$jamKe <= (int)$leave->jam_ke_selesai) {
                        return $leave;
                    }
                }
            } else {
                // Tipe harian / seharian penuh
                return $leave;
            }
        }

        return null;
    }

    /**
     * Label keterbacaan jam izin beserta rentang waktu
     */
    public function getWaktuIzinLabelAttribute(): string
    {
        if ($this->tipe_izin === 'jam_tertentu') {
            $hours = is_array($this->jam_ke) ? $this->jam_ke : json_decode($this->jam_ke ?? '[]', true);
            if (!empty($hours)) {
                $hours = array_map('intval', $hours);
                sort($hours);

                $min = min($hours);
                $max = max($hours);
                $startTime = Schedule::getJamTime($min);
                $endTime = Schedule::getJamTime($max);

                $jamList = 'Jam Ke-' . implode(', ', $hours);
                if ($startTime && $endTime) {
                    $startParts = explode(' - ', $startTime);
                    $endParts = explode(' - ', $endTime);
                    $startStr = trim($startParts[0] ?? '');
                    $endStr = trim($endParts[1] ?? ($startParts[1] ?? ''));
                    return "{$jamList} ({$startStr} - {$endStr})";
                }
                return $jamList;
            }

            if ($this->jam_ke_mulai !== null && $this->jam_ke_selesai !== null) {
                $min = (int)$this->jam_ke_mulai;
                $max = (int)$this->jam_ke_selesai;
                $startTime = Schedule::getJamTime($min);
                $endTime = Schedule::getJamTime($max);
                $jamList = "Jam Ke-{$min} s/d {$max}";
                if ($startTime && $endTime) {
                    $startParts = explode(' - ', $startTime);
                    $endParts = explode(' - ', $endTime);
                    $startStr = trim($startParts[0] ?? '');
                    $endStr = trim($endParts[1] ?? ($startParts[1] ?? ''));
                    return "{$jamList} ({$startStr} - {$endStr})";
                }
                return $jamList;
            }

            return 'Jam Tertentu';
        }

        if ($this->tanggal_mulai && $this->tanggal_selesai) {
            $tglMulai = \Carbon\Carbon::parse($this->tanggal_mulai)->format('Y-m-d');
            $tglSelesai = \Carbon\Carbon::parse($this->tanggal_selesai)->format('Y-m-d');
            if ($tglMulai !== $tglSelesai) {
                $diff = \Carbon\Carbon::parse($tglMulai)->diffInDays(\Carbon\Carbon::parse($tglSelesai)) + 1;
                return "Harian ({$diff} Hari)";
            }
        }

        return '1 Hari Penuh';
    }

    /**
     * Keterangan detail rincian jam keberapa beserta waktu
     */
    public function getWaktuIzinDetailAttribute(): ?string
    {
        if ($this->tipe_izin !== 'jam_tertentu') {
            return null;
        }

        $hours = is_array($this->jam_ke) ? $this->jam_ke : json_decode($this->jam_ke ?? '[]', true);
        if (empty($hours)) {
            if ($this->jam_ke_mulai !== null && $this->jam_ke_selesai !== null) {
                $hours = range((int)$this->jam_ke_mulai, (int)$this->jam_ke_selesai);
            } else {
                return null;
            }
        }

        $hours = array_map('intval', $hours);
        sort($hours);

        $details = [];
        foreach ($hours as $h) {
            $details[] = Schedule::getJamLabelWithTime($h);
        }

        return implode(' • ', $details);
    }

    protected static function booted()
    {
        static::saving(function (TeacherLeaveRequest $leave) {
            if (empty($leave->status)) {
                $leave->status = 'diajukan';
            }

            if (empty($leave->tanggal_selesai) || $leave->tipe_izin === 'jam_tertentu') {
                $leave->tanggal_selesai = $leave->tanggal_mulai ?: now()->toDateString();
            }

            if ($leave->tipe_izin === 'jam_tertentu') {
                $hours = is_array($leave->jam_ke) ? $leave->jam_ke : json_decode($leave->jam_ke ?? '[]', true);
                if (!empty($hours)) {
                    $hours = array_map('intval', $hours);
                    $leave->jam_ke_mulai = min($hours);
                    $leave->jam_ke_selesai = max($hours);
                }
            }
        });

        static::saved(function (TeacherLeaveRequest $leave) {
            $leave->syncToAttendance();
        });

        static::deleted(function (TeacherLeaveRequest $leave) {
            TeacherAttendance::withoutGlobalScopes()
                ->where('teacher_leave_request_id', $leave->id)
                ->delete();
        });
    }

    /**
     * Sinkronkan perizinan guru ke rekap presensi guru beserta guru pengganti (inval)
     */
    public function syncToAttendance(): void
    {
        if ($this->status === 'ditolak') {
            TeacherAttendance::withoutGlobalScopes()
                ->where('teacher_leave_request_id', $this->id)
                ->delete();
            return;
        }

        $start = \Carbon\Carbon::parse($this->tanggal_mulai);
        $end = $this->tanggal_selesai ? \Carbon\Carbon::parse($this->tanggal_selesai) : $start->copy();
        if ($end->lessThan($start)) {
            $end = $start->copy();
        }

        $alasanLower = strtolower($this->alasan ?? '');
        $statusPresensi = 'izin';
        if (str_contains($alasanLower, 'sakit')) {
            $statusPresensi = 'sakit';
        } elseif (str_contains($alasanLower, 'dinas') || str_contains($alasanLower, 'tugas')) {
            $statusPresensi = 'dinas_luar';
        }

        $leaveHours = is_array($this->jam_ke) ? array_map('intval', $this->jam_ke) : [];
        $activeDates = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $dateStr = $cursor->toDateString();
            $activeDates[] = $dateStr;
            $hariName = Schedule::getIndonesianDayName($cursor);

            $schedules = Schedule::withoutGlobalScopes()
                ->where('teacher_id', $this->teacher_id)
                ->where('hari', $hariName)
                ->get();

            $matchedAny = false;
            foreach ($schedules as $sched) {
                $applies = true;
                if ($this->tipe_izin === 'jam_tertentu' && !empty($leaveHours)) {
                    $applies = in_array((int)$sched->jam_ke, $leaveHours);
                }

                if ($applies) {
                    $matchedAny = true;
                    TeacherAttendance::withoutGlobalScopes()->updateOrCreate(
                        [
                            'teacher_id' => $this->teacher_id,
                            'tanggal' => $dateStr,
                            'schedule_id' => $sched->id,
                        ],
                        [
                            'institution_id' => $sched->classroom?->institution_id ?? ($this->institution_id ?: 2),
                            'classroom_id' => $sched->classroom_id,
                            'subject_id' => $sched->subject_id,
                            'jam_ke' => $sched->jam_ke,
                            'status' => $statusPresensi,
                            'substitute_teacher_id' => $this->substitute_teacher_id,
                            'teacher_leave_request_id' => $this->id,
                            'keterangan' => $this->alasan,
                            'attachment' => $this->supporting_letter,
                        ]
                    );
                }
            }

            // Jika tidak ada jadwal mengajar pada hari tersebut, tetap catat ke rekap presensi harian
            if (!$matchedAny) {
                TeacherAttendance::withoutGlobalScopes()->updateOrCreate(
                    [
                        'teacher_id' => $this->teacher_id,
                        'tanggal' => $dateStr,
                        'teacher_leave_request_id' => $this->id,
                    ],
                    [
                        'institution_id' => $this->institution_id ?: 2,
                        'schedule_id' => null,
                        'classroom_id' => null,
                        'subject_id' => null,
                        'jam_ke' => null,
                        'status' => $statusPresensi,
                        'substitute_teacher_id' => $this->substitute_teacher_id,
                        'keterangan' => $this->alasan,
                        'attachment' => $this->supporting_letter,
                    ]
                );
            }

            $cursor->addDay();
        }

        TeacherAttendance::withoutGlobalScopes()
            ->where('teacher_leave_request_id', $this->id)
            ->whereNotIn('tanggal', $activeDates)
            ->delete();
    }
}
