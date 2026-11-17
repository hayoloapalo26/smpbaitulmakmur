<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingJournal extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected $casts = [
        'kegiatan_rutin' => 'array',
        'catatan_karakter' => 'array',
        'tp_ids' => 'array',
        'is_late' => 'boolean',
        'is_kegiatan_lain' => 'boolean',
        'waka_approved_at' => 'datetime',
        'kepsek_approved_at' => 'datetime',
    ];

    public const LATE_STATUS_ON_TIME = 'on_time';
    public const LATE_STATUS_PENDING_WAKA = 'pending_waka';
    public const LATE_STATUS_APPROVED_WAKA = 'approved_waka';
    public const LATE_STATUS_REJECTED_WAKA = 'rejected_waka';

    public const ACC_KEPSEK_PENDING = 'pending';
    public const ACC_KEPSEK_APPROVED = 'approved';
    public const ACC_KEPSEK_NEED_REVISION = 'need_revision';

    /**
     * Accessor untuk form multiple choice schedule_ids
     */
    public function getScheduleIdsAttribute(): array
    {
        return $this->schedule_id ? [(int) $this->schedule_id] : [];
    }

    /**
     * Mutator untuk schedule_ids
     */
    public function setScheduleIdsAttribute($value): void
    {
        $ids = (array) $value;
        if (!empty($ids)) {
            $this->attributes['schedule_id'] = (int) reset($ids);
        }
    }

    /**
     * Accessor untuk form presensi siswa per mapel
     */
    public function getStudentAttendancesAttribute(): array
    {
        return $this->subjectAttendances()->pluck('status', 'student_id')->toArray();
    }

    /**
     * Mutator untuk form presensi siswa per mapel
     */
    public function setStudentAttendancesAttribute($value): void
    {
        // Virtual attribute for form hydration & attendance processing
    }

    protected static function booted()
    {
        static::saving(function (TeachingJournal $journal) {
            // Cek keterlambatan pengisian (melewati pukul 23:59 pada tanggal mengajar)
            if ($journal->tanggal) {
                $endOfDay = \Carbon\Carbon::parse($journal->tanggal)->endOfDay();
                $isLate = now()->isAfter($endOfDay);

                if ($isLate) {
                    $journal->is_late = true;
                    if (empty($journal->late_status) || $journal->late_status === self::LATE_STATUS_ON_TIME) {
                        $journal->late_status = self::LATE_STATUS_PENDING_WAKA;
                    }
                } else {
                    if (empty($journal->late_status)) {
                        $journal->is_late = false;
                        $journal->late_status = self::LATE_STATUS_ON_TIME;
                    }
                }
            }

            if (empty($journal->status_acc_kepsek)) {
                $journal->status_acc_kepsek = self::ACC_KEPSEK_PENDING;
            }
            if ($journal->schedule_id) {
                $schedule = Schedule::withoutGlobalScopes()->find($journal->schedule_id);
                if ($schedule) {
                    $journal->classroom_id = $journal->classroom_id ?: $schedule->classroom_id;
                    $journal->subject_id = $journal->subject_id ?: $schedule->subject_id;
                    $journal->jam_ke = $journal->jam_ke ?: $schedule->jam_ke;
                    $journal->teacher_id = $journal->teacher_id ?: $schedule->teacher_id;
                    if (\Illuminate\Support\Facades\Schema::hasColumn('teaching_journals', 'institution_id')) {
                        $journal->institution_id = $journal->institution_id ?: $schedule->institution_id;
                    } else {
                        unset($journal->institution_id);
                    }
                }
            } elseif ($journal->teacher_id && $journal->tanggal && $journal->jam_ke) {
                $hari = Schedule::getIndonesianDayName($journal->tanggal);
                $schedule = Schedule::withoutGlobalScopes()
                    ->where('teacher_id', $journal->teacher_id)
                    ->where('hari', $hari)
                    ->where('jam_ke', $journal->jam_ke)
                    ->when($journal->classroom_id, fn ($q) => $q->where('classroom_id', $journal->classroom_id))
                    ->first();

                if ($schedule) {
                    $journal->schedule_id = $schedule->id;
                }
            }

            if (empty($journal->tp_ids) && !empty($journal->rppm_id)) {
                $rppm = Rppm::find($journal->rppm_id);
                if ($rppm && !empty($rppm->selected_tp_ids)) {
                    $journal->tp_ids = array_values(array_map('intval', (array) $rppm->selected_tp_ids));
                }
            }

            if (!empty($journal->tp_ids) && is_array($journal->tp_ids)) {
                $journal->tp_ids = array_values(array_unique(array_map('intval', $journal->tp_ids)));
            }

            // Otomatis lengkapi data jika jam mengajar bertepatan dengan kegiatan siswa / kegiatan sekolah lain (contoh: Apel OSIS, Upacara, dll)
            if ($journal->is_kegiatan_lain && !empty($journal->nama_kegiatan_lain)) {
                if (empty($journal->materi) || $journal->materi === '-') {
                    $journal->materi = "[Kegiatan Siswa] {$journal->nama_kegiatan_lain}";
                }
                if (empty($journal->rencana_kegiatan_guru)) {
                    $journal->rencana_kegiatan_guru = "Mendampingi & mengawasi siswa dalam pelaksanaan {$journal->nama_kegiatan_lain}";
                }
                if (empty($journal->kegiatan_siswa)) {
                    $journal->kegiatan_siswa = "Siswa mengikuti dan melaksanakan kegiatan {$journal->nama_kegiatan_lain} dengan tertib dan khidmat";
                }
            }

            if (empty($journal->materi)) {
                if (!empty($journal->rppm_id)) {
                    $journal->materi = Rppm::where('id', $journal->rppm_id)->value('topik');
                }
                if (empty($journal->materi) && !empty($journal->tp_ids) && is_array($journal->tp_ids)) {
                    $journal->materi = TujuanPembelajaran::whereIn('id', $journal->tp_ids)->pluck('materi')->filter()->unique()->implode(', ');
                }
                if (empty($journal->materi)) {
                    $journal->materi = $journal->rencana_kegiatan_guru ?? '-';
                }
            }
        });

        static::saved(function (TeachingJournal $journal) {
            // Cek apakah jurnal ini diisi oleh guru pengganti atau guru piket
            $guruPiketIds = User::withoutGlobalScopes()
                ->where(fn ($q) => $q->where('name', 'Guru Piket')->orWhereHas('roles', fn ($r) => $r->where('name', 'guru_piket')))
                ->pluck('id')
                ->toArray();

            $matchingSubIds = array_unique(array_merge([$journal->teacher_id], $guruPiketIds));

            $leaveAttendance = TeacherAttendance::withoutGlobalScopes()
                ->where('schedule_id', $journal->schedule_id)
                ->whereDate('tanggal', $journal->tanggal)
                ->whereIn('substitute_teacher_id', $matchingSubIds)
                ->first();

            if ($leaveAttendance) {
                $teacherName = $journal->teacher?->name ?? 'Guru Pengganti';
                $leaveAttendance->update([
                    'keterangan' => trim(($leaveAttendance->keterangan ? $leaveAttendance->keterangan . ' • ' : '') . 'Kelas telah terlaksana diajar oleh Guru Pengganti (' . $teacherName . ')'),
                ]);
            }

            // Otomatis catat Presensi Guru (Hadir) berdasarkan input Jurnal Mengajar
            $existing = TeacherAttendance::withoutGlobalScopes()
                ->where('teaching_journal_id', $journal->id)
                ->first();

            if (!$existing && $journal->schedule_id) {
                $existing = TeacherAttendance::withoutGlobalScopes()
                    ->where('teacher_id', $journal->teacher_id)
                    ->whereDate('tanggal', $journal->tanggal)
                    ->where('schedule_id', $journal->schedule_id)
                    ->first();
            }

            if (!$existing && $journal->jam_ke) {
                $existing = TeacherAttendance::withoutGlobalScopes()
                    ->where('teacher_id', $journal->teacher_id)
                    ->whereDate('tanggal', $journal->tanggal)
                    ->where('jam_ke', $journal->jam_ke)
                    ->whereNull('teaching_journal_id')
                    ->first();
            }

            $leaveRecord = \App\Models\TeacherLeaveRequest::getActiveLeaveFor($journal->teacher_id, $journal->tanggal, $journal->jam_ke);
            $isDinasLuar = $leaveRecord?->isDinasLuar() || ($existing && $existing->status === 'dinas_luar');

            $statusToSet = $isDinasLuar ? 'dinas_luar' : 'masuk';
            $keteranganToSet = $isDinasLuar
                ? 'Dinas Luar: ' . ($leaveRecord?->alasan ?? ($existing?->keterangan ?? 'Tugas Dinas Luar')) . ' (Jurnal mengajar telah diisi guru bersangkutan)'
                : ($journal->is_kegiatan_lain && !empty($journal->nama_kegiatan_lain)
                    ? 'Masuk mengajar (Pendampingan Kegiatan Siswa: ' . $journal->nama_kegiatan_lain . ')'
                    : ($leaveAttendance ? 'Masuk mengajar sebagai Guru Pengganti (Inval)' : ($existing?->keterangan ?? 'Masuk mengajar sesuai Jurnal Mengajar')));

            $payload = [
                'institution_id' => $journal->schedule?->institution_id ?? $journal->classroom?->institution_id ?? $journal->teacher?->institution_id,
                'teacher_id' => $journal->teacher_id,
                'schedule_id' => $journal->schedule_id,
                'teaching_journal_id' => $journal->id,
                'classroom_id' => $journal->classroom_id,
                'subject_id' => $journal->subject_id,
                'jam_ke' => $journal->jam_ke,
                'tanggal' => $journal->tanggal,
                'status' => $statusToSet,
                'keterangan' => $keteranganToSet,
            ];

            if ($isDinasLuar && ($leaveRecord?->substitute_teacher_id || $existing?->substitute_teacher_id)) {
                $payload['substitute_teacher_id'] = $existing?->substitute_teacher_id ?? $leaveRecord?->substitute_teacher_id;
            }
            if ($leaveRecord) {
                $payload['teacher_leave_request_id'] = $existing?->teacher_leave_request_id ?? $leaveRecord->id;
            }

            if ($existing) {
                $existing->update($payload);
            } else {
                TeacherAttendance::create($payload);
            }

            // Otomatis sinkronkan catatan karakter siswa dari Jurnal Mengajar ke CharacterScoreLog & Rekapitulasi Skor Karakter
            $journal->syncCharacterScoreLogs();
        });

        static::deleted(function (TeachingJournal $journal) {
            TeacherAttendance::withoutGlobalScopes()
                ->where('teaching_journal_id', $journal->id)
                ->delete();

            CharacterScoreLog::withoutGlobalScopes()
                ->where('description', 'like', "[Jurnal #{$journal->id}]%")
                ->get()
                ->each(fn ($log) => $log->delete());
        });
    }

    /**
     * Sinkronkan catatan karakter siswa (baik / kurang baik) ke CharacterScoreLog & Rekapitulasi Skor Karakter Siswa.
     */
    public function syncCharacterScoreLogs(): void
    {
        $charEntries = $this->catatan_karakter;
        if (!is_array($charEntries)) {
            return;
        }

        $prefix = "[Jurnal #{$this->id}]";

        // Hapus log lama dari jurnal ini agar sinkron dan tidak duplikasi saat diedit
        CharacterScoreLog::withoutGlobalScopes()
            ->where('description', 'like', "{$prefix}%")
            ->get()
            ->each(fn ($log) => $log->delete());

        foreach ($charEntries as $item) {
            $studentId = $item['student_id'] ?? null;
            $catatan = trim($item['catatan'] ?? '');
            $kategori = trim($item['kategori'] ?? 'Karakter Mapel');
            $type = $item['type'] ?? 'baik';

            if ($studentId && ($catatan !== '' || $kategori !== '')) {
                $student = Student::withoutGlobalScopes()->find($studentId);
                if ($student) {
                    $isBaik = in_array(strtolower($type), ['baik', 'positif', '+']);
                    $subjectName = $this->subject?->name ?? 'Mapel';
                    $descriptionText = "{$prefix} [{$subjectName}] " . ($catatan ?: $kategori);

                    CharacterScoreLog::create([
                        'student_id'     => $student->id,
                        'source'         => 'guru_kelas',
                        'category'       => $kategori ?: ($isBaik ? 'Karakter Baik' : 'Karakter Kurang Baik'),
                        'description'    => $descriptionText,
                        'point_change'   => $isBaik ? 1 : -1,
                        'tanggal'        => $this->tanggal,
                        'submitted_by'   => $this->teacher_id ?: \Illuminate\Support\Facades\Auth::id(),
                        'institution_id' => $student->institution_id ?: ($this->institution_id ?: 2),
                    ]);
                }
            }
        }
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function rppm()
    {
        return $this->belongsTo(Rppm::class, 'rppm_id');
    }

    public function getSelectedTpsAttribute()
    {
        if (!empty($this->tp_ids) && is_array($this->tp_ids)) {
            $tps = TujuanPembelajaran::whereIn('id', $this->tp_ids)->get();
            if ($tps->isNotEmpty()) {
                return $tps;
            }
        }

        if ($this->rppm) {
            return $this->rppm->selected_tps;
        }

        return collect();
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    public function teacherAttendance()
    {
        return $this->hasOne(TeacherAttendance::class, 'teaching_journal_id');
    }

    public function subjectAttendances()
    {
        return $this->hasMany(SubjectAttendance::class, 'teaching_journal_id');
    }

    public function wakaApprover()
    {
        return $this->belongsTo(User::class, 'waka_approved_by');
    }

    public function kepsekApprover()
    {
        return $this->belongsTo(User::class, 'kepsek_approved_by');
    }

    public function scopeReadyForKepsek($query)
    {
        return $query->where(function ($q) {
            $q->where('is_late', false)
              ->orWhere('late_status', self::LATE_STATUS_APPROVED_WAKA);
        });
    }

    public function scopePendingWaka($query)
    {
        return $query->where('is_late', true)
                     ->where('late_status', self::LATE_STATUS_PENDING_WAKA);
    }

    public function isReadyForKepsek(): bool
    {
        return !$this->is_late || $this->late_status === self::LATE_STATUS_APPROVED_WAKA;
    }

    public function isApprovedByKepsek(): bool
    {
        return $this->status_acc_kepsek === self::ACC_KEPSEK_APPROVED;
    }

    public static function getJournalDeadline(string|\Carbon\Carbon $tanggal, int|string|null $jamKe = null): \Carbon\Carbon
    {
        $dateStr = \Carbon\Carbon::parse($tanggal)->format('Y-m-d');
        $endTimeStr = '15:00:00';

        if ($jamKe !== null && $jamKe !== '') {
            $slotTime = Schedule::getJamTime($jamKe);
            if ($slotTime && str_contains($slotTime, '-')) {
                $parts = explode('-', $slotTime);
                $endPart = trim(end($parts));
                $endNormalized = str_replace('.', ':', $endPart);
                if (strlen($endNormalized) === 5) {
                    $endTimeStr = $endNormalized . ':00';
                }
            }
        }

        return \Carbon\Carbon::parse("{$dateStr} {$endTimeStr}")->addHours(24);
    }

    public static function isPastDeadline(string|\Carbon\Carbon $tanggal, int|string|null $jamKe = null): bool
    {
        return now()->isAfter(static::getJournalDeadline($tanggal, $jamKe));
    }
}
