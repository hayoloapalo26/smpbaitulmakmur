<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\CharacterScoreLog;
use Carbon\Carbon;

class TeacherCommitment extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string $view = 'filament.staff.pages.teacher-commitment';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $title = 'Pemantauan Komitmen Guru';

    

    protected function getViewData(): array
    {
        $today = Carbon::today()->toDateString();
        
        $teachers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas']);
        })->get();
        
        $filledTeacherIds = CharacterScoreLog::whereDate('tanggal', $today)
            ->where('source', 'guru_kelas')
            ->whereNotNull('submitted_by')
            ->pluck('submitted_by')->toArray();
            
        $report = [];
        foreach ($teachers as $teacher) {
            $report[] = [
                'name' => $teacher->name,
                'email' => $teacher->email,
                'status' => in_array($teacher->id, $filledTeacherIds) ? 'Sudah Isi' : 'Belum Isi',
            ];
        }

        return [
            'report' => $report,
            'today' => $today,
        ];
    }
}
