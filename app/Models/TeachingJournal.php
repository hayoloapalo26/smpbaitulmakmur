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
    ];

    protected static function booted()
    {
        static::creating(function (TeachingJournal $journal) {
            $user = auth()->user();
            $isAdmin = $user && $user->hasAnyRole(['waka_kurikulum', 'superadmin']);

            if (!$isAdmin && $journal->tanggal) {
                if (static::isPastDeadline($journal->tanggal, $journal->jam_ke)) {
                    $deadline = static::getJournalDeadline($journal->tanggal, $journal->jam_ke)->translatedFormat('d M Y, H:i');
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'schedule_id' => "Batas waktu pengisian jurnal mengajar telah berakhir (maksimal 24 jam setelah jadwal mengajar selesai: {$deadline} WIB). Anda tidak dapat lagi menginputkan jurnal ini.",
                    ]);
                }
            }
        });

        static::saving(function (TeachingJournal $journal) {
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

            $payload = [
                'institution_id' => $journal->schedule?->institution_id ?? $journal->classroom?->institution_id ?? $journal->teacher?->institution_id,
                'teacher_id' => $journal->teacher_id,
                'schedule_id' => $journal->schedule_id,
                'teaching_journal_id' => $journal->id,
                'classroom_id' => $journal->classroom_id,
                'subject_id' => $journal->subject_id,
                'jam_ke' => $journal->jam_ke,
                'tanggal' => $journal->tanggal,
                'status' => 'masuk',
                'keterangan' => $leaveAttendance ? 'Masuk mengajar sebagai Guru Pengganti (Inval)' : ($existing?->keterangan ?? 'Masuk mengajar sesuai Jurnal Mengajar'),
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                TeacherAttendance::create($payload);
            }
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
        if (empty($this->tp_ids)) {
            return collect();
        }

        return TujuanPembelajaran::whereIn('id', $this->tp_ids)->get();
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
