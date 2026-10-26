<?php

namespace App\Observers;

use App\Models\PrayerAttendance;
use App\Models\CharacterScoreLog;

class PrayerAttendanceObserver
{
    public function created(PrayerAttendance $prayerAttendance): void
    {
        if ($prayerAttendance->is_khusyu === false && $prayerAttendance->status === 'hadir') {
            CharacterScoreLog::create([
                'student_id' => $prayerAttendance->student_id,
                'source' => 'guru_piket',
                'category' => 'kepribadian',
                'description' => 'Tidak khusyu saat sholat berjamaah.',
                'point_change' => -1,
                'tanggal' => $prayerAttendance->tanggal,
            ]);
        }
    }
    
    public function updated(PrayerAttendance $prayerAttendance): void
    {
        if ($prayerAttendance->isDirty('is_khusyu') && $prayerAttendance->is_khusyu === false && $prayerAttendance->status === 'hadir') {
            CharacterScoreLog::create([
                'student_id' => $prayerAttendance->student_id,
                'source' => 'guru_piket',
                'category' => 'kepribadian',
                'description' => 'Tidak khusyu saat sholat berjamaah.',
                'point_change' => -1,
                'tanggal' => $prayerAttendance->tanggal,
            ]);
        }
    }
}
