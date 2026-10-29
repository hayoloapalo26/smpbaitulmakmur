<?php

namespace App\Filament\Staff\Widgets;

use App\Models\TeacherLeaveRequest;
use App\Models\TeachingMaterial;
use App\Models\Student;
use App\Models\User;
use App\Models\CharacterScoreLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardStats extends BaseWidget
{
    public static function canView(): bool
    {
        return Auth::user()->hasRole('kepala_sekolah');
    }

    protected function getStats(): array
    {
        $pendingLeaves = TeacherLeaveRequest::where('status', 'disetujui_wakakur')->count();
        $pendingMaterials = TeachingMaterial::where('status', 'direview_wakakur')->count();
        $avgScore = Student::avg('character_score');
        
        $today = Carbon::today()->toDateString();
        $filledTeacherIds = CharacterScoreLog::whereDate('tanggal', $today)
            ->where('source', 'guru_kelas')
            ->whereNotNull('submitted_by')
            ->pluck('submitted_by')->unique()->toArray();
            
        $totalTeachers = User::whereHas('roles', function($q) {
            $q->whereIn('name', array_merge(['guru_mapel', 'wali_kelas'], User::QURAN_TEACHER_ROLES));
        })->count();
        
        $missingCount = max(0, $totalTeachers - count($filledTeacherIds));
        
        return [
            Stat::make('Izin Menunggu Persetujuan', $pendingLeaves)
                ->description('Dari Waka Kurikulum')
                ->descriptionIcon('heroicon-m-clock')
                ->url(route('filament.staff.resources.teacher-leave-requests.index')),
                
            Stat::make('Perangkat Ajar (Perlu Direview)', $pendingMaterials)
                ->description('Dari Waka Kurikulum')
                ->descriptionIcon('heroicon-m-document-text')
                ->url(route('filament.staff.resources.teaching-materials.index')),
                
            Stat::make('Rata-Rata Karakter', number_format((float)$avgScore, 1))
                ->description('Skor rata-rata seluruh siswa')
                ->descriptionIcon('heroicon-m-star')
                ->url(route('filament.staff.resources.students.index')),
                
            Stat::make('Guru Belum Isi To-Do List', $missingCount)
                ->description('Berdasarkan aktivitas hari ini')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger')
                ->url(route('filament.staff.pages.teacher-commitment')),
        ];
    }
}
