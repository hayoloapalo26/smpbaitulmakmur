<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\TeacherAttendance;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class NotifyAbsentTeachers extends Command
{
    protected $signature = 'app:notify-absent-teachers';
    protected $description = 'Send notification to teachers who have not marked their attendance today';

    public function handle()
    {
        $today = Carbon::today()->toDateString();
        $teachers = User::whereHas('roles', function($q) {
            $q->whereIn('name', array_merge(['guru_mapel', 'wali_kelas'], User::QURAN_TEACHER_ROLES));
        })->get();

        $absentTeachers = [];
        foreach ($teachers as $teacher) {
            $hasAttendance = TeacherAttendance::where('teacher_id', $teacher->id)
                ->whereDate('tanggal', $today)
                ->exists();

            if (!$hasAttendance) {
                // Notifikasi ke guru bersangkutan (In-App Database Notification)
                Notification::make()
                    ->title('Pengingat Presensi')
                    ->body('Anda belum mengisi presensi kehadiran hari ini. Harap segera melapor.')
                    ->warning()
                    ->sendToDatabase($teacher);
                
                // Nantinya bisa ditambahkan channel WhatsApp di sini:
                // WhatsAppService::send($teacher->phone, 'Anda belum mengisi presensi...');
                
                $absentTeachers[] = $teacher->name;
            }
        }

        $this->info('Notified ' . count($absentTeachers) . ' teachers.');
    }
}
