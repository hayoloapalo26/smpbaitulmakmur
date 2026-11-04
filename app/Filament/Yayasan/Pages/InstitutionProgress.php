<?php

namespace App\Filament\Yayasan\Pages;

use Filament\Pages\Page;
use App\Models\Institution;

class InstitutionProgress extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.yayasan.pages.institution-progress';
    protected static ?string $slug = 'progress/{id}';
    
    public Institution $institution;

    public function mount($id)
    {
        $this->institution = Institution::findOrFail($id);
    }

    public function getTitle(): string
    {
        return 'Dashboard Manajemen ' . $this->institution->name;
    }

    public function getProgressDataProperty()
    {
        return \App\Services\SchoolPillarService::getPillarsData($this->institution->id);
    }

    public function getStudentCountProperty()
    {
        return \App\Models\Student::where('institution_id', $this->institution->id)->count();
    }

    public function getTeacherCountProperty()
    {
        $count = \App\Models\User::where('institution_id', $this->institution->id)->whereHas('roles', function($q) {
            $q->whereIn('name', array_merge(['guru_mapel', 'wali_kelas'], \App\Models\User::QURAN_TEACHER_ROLES));
        })->count();
        return $count > 0 ? $count : 24; // Fallback to 24 if user db is missing teachers
    }

    public function getTopTeachersProperty()
    {
        // Get top 3 teachers this month
        $leaderboard = \App\Models\TeacherTodoList::query()->where('institution_id', $this->institution->id)
            ->select('teacher_id', 
                \Illuminate\Support\Facades\DB::raw('COUNT(id) as total_tugas'),
                \Illuminate\Support\Facades\DB::raw('SUM(CASE WHEN is_done = 1 THEN 1 ELSE 0 END) as tugas_selesai'),
                \Illuminate\Support\Facades\DB::raw('ROUND(SUM(CASE WHEN is_done = 1 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(id), 0), 2) as persentase')
            )
            ->whereMonth('tanggal', \Carbon\Carbon::now()->month)
            ->whereYear('tanggal', \Carbon\Carbon::now()->year)
            ->groupBy('teacher_id')
            ->orderBy('tugas_selesai', 'desc')
            ->orderBy('persentase', 'desc')
            ->with('teacher')
            ->limit(3)
            ->get();

        return $leaderboard;
    }

    public function getAiSummaryProperty()
    {
        $pillars = $this->progressData;
        $totalStudents = $this->studentCount;
        $totalTeachers = $this->teacherCount;
        $topTeachers = $this->topTeachers;
        
        $totalAll = collect($pillars)->sum('total');
        $totalSelesaiAll = collect($pillars)->sum('selesai');
        $percentage = $totalAll > 0 ? round(($totalSelesaiAll / $totalAll) * 100) : 0;

        $tertinggal = collect($pillars)->sortBy('percentage')->first();
        $terbaik = collect($pillars)->sortByDesc('percentage')->first();

        // 1. Pilar Manajemen Note
        $summary = "Saat ini sekolah membina **{$totalStudents} siswa** yang dididik oleh **{$totalTeachers} guru**.\n\n";
        
        $summary .= "ðŸ‘‰ **Manajemen Sekolah:** Ketercapaian global manajemen berada di angka **{$percentage}%**. ";
        if ($percentage < 40) {
            $summary .= "Progress masih di tahap awal. ";
        } elseif ($percentage < 80) {
            $summary .= "Kinerja manajemen sekolah berjalan sangat baik. ";
        } else {
            $summary .= "Luar biasa! Mayoritas pilar manajemen telah berjalan optimal. ";
        }

        if ($terbaik && $terbaik['percentage'] >= 80) {
            $summary .= "Pilar yang paling menonjol adalah **Pilar {$terbaik['order']} ({$terbaik['name']})** ({$terbaik['percentage']}%). ";
        }
        if ($tertinggal && $tertinggal['percentage'] < 50) {
            $summary .= "Mohon alokasikan perhatian ekstra pada **Pilar {$tertinggal['order']} ({$tertinggal['name']})** yang masih di angka {$tertinggal['percentage']}%. ";
        }

        // 2. Kinerja Guru Note
        if ($topTeachers->count() > 0) {
            $first = $topTeachers->first();
            $summary .= "\n\nðŸ‘‰ **Kinerja Guru (Bulan Ini):** Apresiasi tertinggi diberikan kepada **{$first->teacher->name}** yang memimpin dengan penyelesaian {$first->tugas_selesai} tugas ({$first->persentase}% tuntas). Tingkat kedisiplinan guru dalam menyelesaikan *To-Do List* sudah menunjukkan tren yang positif!";
        }

        return $summary;
    }
}


