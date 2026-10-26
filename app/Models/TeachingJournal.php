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
    ];

    protected static function booted()
    {
        static::saving(function (TeachingJournal $journal) {
            if ($journal->schedule_id) {
                $schedule = Schedule::withoutGlobalScopes()->find($journal->schedule_id);
                if ($schedule) {
                    $journal->classroom_id = $journal->classroom_id ?: $schedule->classroom_id;
                    $journal->subject_id = $journal->subject_id ?: $schedule->subject_id;
                    $journal->jam_ke = $journal->jam_ke ?: $schedule->jam_ke;
                    $journal->teacher_id = $journal->teacher_id ?: $schedule->teacher_id;
                    $journal->institution_id = $journal->institution_id ?: $schedule->institution_id;
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
                'institution_id' => $journal->institution_id,
                'teacher_id' => $journal->teacher_id,
                'schedule_id' => $journal->schedule_id,
                'teaching_journal_id' => $journal->id,
                'classroom_id' => $journal->classroom_id,
                'subject_id' => $journal->subject_id,
                'jam_ke' => $journal->jam_ke,
                'tanggal' => $journal->tanggal,
                'status' => 'hadir',
                'keterangan' => $leaveAttendance ? 'Hadir mengajar sebagai Guru Pengganti (Inval)' : ($existing?->keterangan),
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
}
