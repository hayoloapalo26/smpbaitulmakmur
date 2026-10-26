<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\CharacterScoreLog;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class NotifyMissingTodoList extends Command
{
    protected $signature = 'app:notify-missing-todo-list';
    protected $description = 'Send notification to teachers who have not inputted to-do list character score today';

    public function handle()
    {
        $today = Carbon::today()->toDateString();
        $teachers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas']);
        })->get();

        $filledTeacherIds = CharacterScoreLog::whereDate('tanggal', $today)
            ->where('source', 'guru_kelas')
            ->whereNotNull('submitted_by')
            ->pluck('submitted_by')->unique()->toArray();

        $missingTeachers = [];
        foreach ($teachers as $teacher) {
            if (!in_array($teacher->id, $filledTeacherIds)) {
                Notification::make()
                    ->title('Pengingat To-Do List Siswa')
                    ->body('Anda belum mengisi jurnal To-Do List Karakter hari ini. Mohon segera diisi.')
                    ->danger()
                    ->sendToDatabase($teacher);
                
                // Nantinya bisa ditambahkan channel WhatsApp di sini:
                // WhatsAppService::send($teacher->phone, 'Anda belum mengisi To-Do List...');
                
                $missingTeachers[] = $teacher->name;
            }
        }

        $this->info('Notified ' . count($missingTeachers) . ' teachers missing to-do list.');
    }
}
