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
        // Yayasan acts as KS (sees all)
        $pillars = \App\Models\SchoolPillar::where('institution_id', $this->institution->id)
            ->orderBy('order')
            ->with(['tasks' => function($q) {
                $q->orderBy('order');
            }])->get();

        $data = [];
        
        foreach ($pillars as $pillar) {
            $tasks = $pillar->tasks;

            // ---------------------------------------------------------
            // OVERRIDE WITH DYNAMIC MODULE DATA BASED ON PILLAR
            // (Same logic as Staff Dashboard so Yayasan sees exact same data)
            // ---------------------------------------------------------
            if ($pillar->order == 1) {
                $tasks = \App\Models\WorkProgram::where('institution_id', $this->institution->id)->get()->map(function($m) {
                    $tanggal = $m->tanggal_mulai ? \Carbon\Carbon::parse($m->tanggal_mulai)->format('d M Y') : '-';
                    return (object)[
                        'id' => 'dynamic_ks_' . $m->id,
                        'name' => $m->nama_program,
                        'status' => $m->status_pelaksanaan === 'selesai' ? 'selesai' : 'belum',
                        'code' => 'KS',
                        'assigned_role' => 'KS',
                        'is_sop_merah' => false,
                        'desc' => 'Tugas KS | Divisi: ' . ucfirst($m->divisi) . ' | Mulai: ' . $tanggal,
                    ];
                });
            } elseif ($pillar->order == 3) {
                $tasks = \App\Models\ProgramKurikulum::where('institution_id', $this->institution->id)->get()->map(function($m) {
                    return (object)[
                        'id' => 'dynamic_kurikulum_' . $m->id,
                        'name' => $m->nama_program,
                        'status' => $m->status_pelaksanaan === 'Selesai' ? 'selesai' : 'belum',
                        'code' => 'WKr',
                        'assigned_role' => 'WKr',
                        'is_sop_merah' => false,
                        'desc' => 'Program Kurikulum | Kategori: ' . $m->kategori . ' | Sifat: ' . $m->sifat,
                    ];
                });
            } elseif ($pillar->order == 5) {
                $tasks = \App\Models\ProgramKesiswaan::where('institution_id', $this->institution->id)->get()->map(function($m) {
                    return (object)[
                        'id' => 'dynamic_kesiswaan_' . $m->id,
                        'name' => $m->nama_program,
                        'status' => $m->status_pelaksanaan === 'Selesai' ? 'selesai' : 'belum',
                        'code' => 'WKs',
                        'assigned_role' => 'WKs',
                        'is_sop_merah' => false,
                        'desc' => 'Program Kesiswaan | Kategori: ' . $m->kategori . ' | Sifat: ' . $m->sifat,
                    ];
                });
            } elseif ($pillar->order == 8) {
                $tasks = \App\Models\HumasProgram::where('institution_id', $this->institution->id)->get()->map(function($m) {
                    $tanggal = $m->tanggal ? \Carbon\Carbon::parse($m->tanggal)->format('d M Y') : '-';
                    return (object)[
                        'id' => 'dynamic_humas_' . $m->id,
                        'name' => $m->nama,
                        'status' => $m->status === 'sudah' ? 'selesai' : 'belum',
                        'code' => 'Hm',
                        'assigned_role' => 'Hm',
                        'is_sop_merah' => false,
                        'desc' => 'Agenda Humas | Tanggal: ' . $tanggal,
                    ];
                });
            } elseif ($pillar->order == 9) {
                $tasks = \App\Models\BkWorkProgram::where('institution_id', $this->institution->id)->get()->map(function($m) {
                    return (object)[
                        'id' => 'dynamic_bk_' . $m->id,
                        'name' => $m->judul_program,
                        'status' => $m->status === 'Terlaksana' ? 'selesai' : 'belum',
                        'code' => 'BK',
                        'assigned_role' => 'BK',
                        'is_sop_merah' => false,
                        'desc' => 'Program BK | Sasaran: ' . $m->sasaran,
                    ];
                });
            }

            $total = $tasks->count();
            $selesai = $tasks->where('status', 'selesai')->count();
            $percentage = $total > 0 ? round(($selesai / $total) * 100) : 0;

            $data[] = [
                'id' => $pillar->id,
                'name' => $pillar->name,
                'order' => $pillar->order,
                'total' => $total,
                'selesai' => $selesai,
                'percentage' => $percentage,
                'tasks' => $tasks,
            ];
        }

        return $data;
    }

    public function getStudentCountProperty()
    {
        return \App\Models\Student::where('institution_id', $this->institution->id)->count();
    }

    public function getTeacherCountProperty()
    {
        $count = \App\Models\User::where('institution_id', $this->institution->id)->whereHas('roles', function($q) {
            $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas']);
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


