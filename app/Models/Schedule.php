<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Schedule extends Model
{
    use \App\Models\Traits\BelongsToInstitution;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(function (Schedule $schedule) {
            if ($schedule->teacher_id && $schedule->subject_id && $schedule->classroom_id) {
                $instId = $schedule->institution_id ?? 2;
                $existing = TeacherSubjectClass::withoutGlobalScopes()
                    ->where('teacher_id', $schedule->teacher_id)
                    ->where('subject_id', $schedule->subject_id)
                    ->where('classroom_id', $schedule->classroom_id)
                    ->first();

                if (!$existing) {
                    // Cek apakah belum ada guru lain di kelas & mapel tersebut
                    $conflict = TeacherSubjectClass::withoutGlobalScopes()
                        ->where('subject_id', $schedule->subject_id)
                        ->where('classroom_id', $schedule->classroom_id)
                        ->first();

                    if (!$conflict) {
                        TeacherSubjectClass::withoutGlobalScopes()->create([
                            'teacher_id' => $schedule->teacher_id,
                            'subject_id' => $schedule->subject_id,
                            'classroom_id' => $schedule->classroom_id,
                            'institution_id' => $instId,
                        ]);
                    }
                }
            }
        });
    }

    public static function getIndonesianDayName($date = null): string
    {
        $carbon = $date ? Carbon::parse($date) : Carbon::now();
        $days = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        return $days[$carbon->dayOfWeek] ?? 'Senin';
    }

    /**
     * Konfigurasi Jam Pelajaran & Jam Mengajar SMP (Jam ke-0 s/d Jam ke-11)
     */
    public static function getJamSlots(): array
    {
        return [
            0 => ['label' => 'Jam Ke-0', 'time' => '07.00 - 08.10', 'full' => 'Jam Ke-0 (07.00 - 08.10)'],
            1 => ['label' => 'Jam Ke-1', 'time' => '08.10 - 08.40', 'full' => 'Jam Ke-1 (08.10 - 08.40)'],
            2 => ['label' => 'Jam Ke-2', 'time' => '08.40 - 09.10', 'full' => 'Jam Ke-2 (08.40 - 09.10)'],
            3 => ['label' => 'Jam Ke-3', 'time' => '09.10 - 09.40', 'full' => 'Jam Ke-3 (09.10 - 09.40)'],
            4 => ['label' => 'Jam Ke-4', 'time' => '10.00 - 10.30', 'full' => 'Jam Ke-4 (10.00 - 10.30)'],
            5 => ['label' => 'Jam Ke-5', 'time' => '10.30 - 11.00', 'full' => 'Jam Ke-5 (10.30 - 11.00)'],
            6 => ['label' => 'Jam Ke-6', 'time' => '11.00 - 11.30', 'full' => 'Jam Ke-6 (11.00 - 11.30)'],
            7 => ['label' => 'Jam Ke-7', 'time' => '11.30 - 12.00', 'full' => 'Jam Ke-7 (11.30 - 12.00)'],
            8 => ['label' => 'Jam Ke-8', 'time' => '13.00 - 13.30', 'full' => 'Jam Ke-8 (13.00 - 13.30)'],
            9 => ['label' => 'Jam Ke-9', 'time' => '13.30 - 14.00', 'full' => 'Jam Ke-9 (13.30 - 14.00)'],
            10 => ['label' => 'Jam Ke-10', 'time' => '14.00 - 14.30', 'full' => 'Jam Ke-10 (14.00 - 14.30)'],
            11 => ['label' => 'Jam Ke-11', 'time' => '14.30 - 15.00', 'full' => 'Jam Ke-11 (14.30 - 15.00)'],
        ];
    }

    public static function getJamOptions(): array
    {
        $options = [];
        foreach (static::getJamSlots() as $jam => $data) {
            $options[$jam] = $data['full'];
        }
        return $options;
    }

    public static function getJamTime(int|string|null $jam): ?string
    {
        if ($jam === null || $jam === '') return null;
        $slots = static::getJamSlots();
        return $slots[(int)$jam]['time'] ?? null;
    }

    public static function getJamLabelWithTime(int|string|null $jam): string
    {
        if ($jam === null || $jam === '') return '-';
        $slots = static::getJamSlots();
        if (isset($slots[(int)$jam])) {
            return $slots[(int)$jam]['full'];
        }
        return "Jam Ke-{$jam}";
    }

    public function getJamKeteranganAttribute(): string
    {
        return static::getJamLabelWithTime($this->jam_ke);
    }

    public function getJamWaktuAttribute(): ?string
    {
        return static::getJamTime($this->jam_ke);
    }

    /**
     * Memeriksa potensi bentrok jadwal untuk kombinasi kelas, guru, hari, dan jam_ke.
     * Mencegah guru mengajar ganda di jam sama, kelas terisi ganda di jam sama, atau duplikasi guru di kelas sama.
     *
     * @param int|string|null $classroomId
     * @param int|string|null $teacherId
     * @param array $days Daftar hari (misal ['Senin', 'Selasa'])
     * @param array $jams Daftar jam_ke (misal [1, 2])
     * @param int|null $ignoreScheduleId ID jadwal yang dikecualikan (saat edit)
     * @return array Daftar pesan bentrok (kosong jika tidak ada bentrok)
     */
    public static function checkClashes($classroomId, $teacherId, array $days, array $jams, ?int $ignoreScheduleId = null): array
    {
        if (empty($classroomId) || empty($teacherId) || empty($days) || empty($jams)) {
            return [];
        }

        $clashes = [];
        $classroom = Classroom::withoutGlobalScopes()->find($classroomId);
        $teacher = User::withoutGlobalScopes()->find($teacherId);
        $className = $classroom?->name ?? "Kelas #{$classroomId}";
        $teacherName = $teacher?->name ?? "Guru #{$teacherId}";

        foreach ($days as $day) {
            foreach ($jams as $jam) {
                if ($jam === null || $jam === '') continue;
                $jamInt = (int) $jam;
                $jamSlot = static::getJamLabelWithTime($jamInt);

                // 1. Cek Bentrok Guru: Apakah guru bersangkutan sudah mengajar di kelas lain (atau kelas ini) pada hari & jam ini?
                $teacherSchedule = static::withoutGlobalScopes()
                    ->where('teacher_id', $teacherId)
                    ->where('hari', $day)
                    ->where('jam_ke', $jamInt)
                    ->when($ignoreScheduleId, fn ($q) => $q->where('id', '!=', $ignoreScheduleId))
                    ->with(['classroom', 'subject'])
                    ->first();

                if ($teacherSchedule) {
                    $otherClass = $teacherSchedule->classroom?->name ?? 'Kelas Lain';
                    $mapel = $teacherSchedule->subject?->name ?? 'Mata Pelajaran';

                    if ((int)$teacherSchedule->classroom_id === (int)$classroomId) {
                        $clashes[] = "Bentrok Jadwal: Guru {$teacherName} sudah memiliki jadwal {$mapel} di {$className} pada hari {$day}, {$jamSlot}.";
                    } else {
                        $clashes[] = "Bentrok Jadwal Guru: Guru {$teacherName} sudah terjadwal mengajar di {$otherClass} (Mapel: {$mapel}) pada hari {$day}, {$jamSlot}.";
                    }
                    continue; // Lanjut ke jam berikutnya agar tidak dobel laporan
                }

                // 2. Cek Bentrok Kelas: Apakah kelas ini sudah diisi guru atau mata pelajaran lain pada hari & jam ini?
                $classSchedule = static::withoutGlobalScopes()
                    ->where('classroom_id', $classroomId)
                    ->where('hari', $day)
                    ->where('jam_ke', $jamInt)
                    ->when($ignoreScheduleId, fn ($q) => $q->where('id', '!=', $ignoreScheduleId))
                    ->with(['teacher', 'subject'])
                    ->first();

                if ($classSchedule) {
                    $otherTeacher = $classSchedule->teacher?->name ?? 'Guru Lain';
                    $mapel = $classSchedule->subject?->name ?? 'Mata Pelajaran';
                    $clashes[] = "Bentrok Jadwal Kelas: {$className} pada hari {$day}, {$jamSlot} sudah terisi pelajaran {$mapel} bersama {$otherTeacher}.";
                }
            }
        }

        return $clashes;
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'schedule_id');
    }

    public function teacherAttendances()
    {
        return $this->hasMany(TeacherAttendance::class, 'schedule_id');
    }
}
