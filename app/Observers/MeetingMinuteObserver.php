<?php

namespace App\Observers;

use App\Models\MeetingMinute;
use App\Models\User;
use Filament\Notifications\Notification;

class MeetingMinuteObserver
{
    public function created(MeetingMinute $meetingMinute): void
    {
        $this->notifyIfUmum($meetingMinute);
    }

    public function updated(MeetingMinute $meetingMinute): void
    {
        // Only notify if it was just changed to umum, or we can assume it only notifies on creation.
        // The prompt says "Setelah notulensi rapat dengan visibility = "umum" disimpan, otomatis kirim notifikasi"
        if ($meetingMinute->isDirty('visibility') && $meetingMinute->visibility === 'umum') {
            $this->notifyIfUmum($meetingMinute);
        }
    }
    
    private function notifyIfUmum(MeetingMinute $meetingMinute)
    {
        if ($meetingMinute->visibility === 'umum') {
            $teachers = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan']);
            })->get();
            
            foreach ($teachers as $teacher) {
                Notification::make()
                    ->title('Notulensi Rapat Baru: ' . $meetingMinute->judul)
                    ->body('Silakan cek detail notulensi rapat tanggal ' . $meetingMinute->tanggal)
                    ->info()
                    ->sendToDatabase($teacher);
            }
        }
    }
}
