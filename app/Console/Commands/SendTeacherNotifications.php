<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\TeacherAttendance;
use App\Models\TeacherTodoList;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class SendTeacherNotifications extends Command
{
    protected $signature = 'notify:teachers';

    protected $description = 'Kirim notifikasi otomatis di dalam sistem ke guru yang tidak hadir atau lupa isi To-Do List';

    public function handle()
    {
        $today = Carbon::today()->format('Y-m-d');
        
        $this->info("Menjalankan pengecekan notifikasi sistem untuk tanggal $today...");

        // Ambil semua guru
        $teachers = User::role(['guru_mapel', 'guru_alquran', 'wali_kelas'])->get();

        foreach ($teachers as $teacher) {
            // 1. Cek Ketidakhadiran
            $attendance = TeacherAttendance::where('teacher_id', $teacher->id)
                ->whereDate('tanggal', $today)
                ->first();

            if ($attendance && in_array($attendance->status, ['sakit', 'izin', 'alpa'])) {
                Notification::make()
                    ->title('Perhatian Absensi: ' . strtoupper($attendance->status))
                    ->body("Bpk/Ibu {$teacher->name}, Anda tercatat " . strtoupper($attendance->status) . " hari ini ($today). Harap pastikan jadwal kelas pengganti (inval) sudah dikoordinasikan.")
                    ->warning()
                    ->sendToDatabase($teacher);
                
                $this->warn("Notif Ketidakhadiran dikirim via sistem ke {$teacher->name}");
            }

            // 2. Cek Lupa Isi To-Do List
            $todoFilled = TeacherTodoList::where('teacher_id', $teacher->id)
                ->whereDate('tanggal', $today)
                ->exists();

            if (!$todoFilled) {
                Notification::make()
                    ->title('Peringatan: To-Do List Belum Diisi')
                    ->body("Bpk/Ibu {$teacher->name}, Anda belum mengisi To-Do List (Komitmen Kinerja) hari ini ($today). Mohon segera lengkapi di menu Piket -> To-Do List Guru.")
                    ->danger()
                    ->sendToDatabase($teacher);

                $this->error("Notif To-Do List dikirim via sistem ke {$teacher->name}");
            }
        }

        $this->info("Selesai mengirim notifikasi in-system otomatis.");
    }
}
