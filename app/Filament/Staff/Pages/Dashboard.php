<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;
use App\Models\SchoolPillarTask;
use Filament\Notifications\Notification;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.staff.pages.dashboard';
    protected static ?string $title = 'Dashboard Utama';

    protected ?array $cachedPillarsData = null;
    protected ?int $cachedStudentCount = null;
    protected ?string $cachedTeacherCount = null;
    protected $cachedTopTeachers = null;
    protected ?string $cachedAiSummary = null;
    protected ?string $cachedLatestNews = null;
    protected $cachedUpcomingSupervisions = null;
    protected ?string $cachedGoodNews = null;
    protected ?string $cachedBadNews = null;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Staff\Widgets\DailyTeachingScheduleWidget::class,
            \App\Filament\Staff\Widgets\TeacherScheduleCalendarWidget::class,
            \App\Filament\Staff\Widgets\PendingTodoReminderWidget::class,
        ];
    }

    public function getPillarsDataProperty()
    {
        if ($this->cachedPillarsData !== null) {
            return $this->cachedPillarsData;
        }

        $user = Auth::user();
        if (!$user) return $this->cachedPillarsData = [];
        
        $codes = [];
        if ($user->hasRole('kepala_sekolah') || $user->hasRole('superadmin')) $codes[] = 'KS';
        if ($user->hasRole('waka_kurikulum')) $codes[] = 'WKr';
        if ($user->hasRole('waka_kesiswaan')) $codes[] = 'WKs';
        if ($user->hasRole('sarpras')) $codes[] = 'SP';
        if ($user->hasRole('humas')) $codes[] = 'Hm';
        if ($user->hasRole('bk')) $codes[] = 'BK';
        if ($user->hasRole('tata_usaha')) $codes[] = 'TU';

        $isKS = in_array('KS', $codes);

        $pillars = \App\Models\SchoolPillar::orderBy('order')->with(['tasks' => function($q) {
            $q->orderBy('order');
        }])->get();

        if (!$isKS && count($codes) > 0) {
            $pillars->each(function($pillar) use ($codes) {
                $pillar->setRelation('tasks', $pillar->tasks->filter(function($task) use ($codes) {
                    foreach ($codes as $code) {
                        if (str_contains($task->assigned_role, $code)) {
                            return true;
                        }
                    }
                    return false;
                }));
            });
            $pillars = $pillars->filter(function($pillar) {
                return $pillar->tasks->count() > 0;
            });
        } elseif (!$isKS && count($codes) === 0) {
            $pillars = collect();
        }

        $data = [];
        $totalAll = 0;
        $totalSelesaiAll = 0;
        
        foreach ($pillars as $pillar) {
            $tasks = $pillar->tasks;

            // ---------------------------------------------------------
            // OVERRIDE WITH DYNAMIC MODULE DATA BASED ON PILLAR
            // ---------------------------------------------------------
            if ($pillar->order == 1) {
                $tasks = \App\Models\ProgramKepsek::all()->map(function($m) {
                    return (object)[
                        'id' => 'dynamic_ks_' . $m->id,
                        'name' => $m->nama_program,
                        'status' => $m->status_pelaksanaan === 'Selesai' ? 'selesai' : 'belum',
                        'code' => 'KS',
                        'assigned_role' => 'KS',
                        'is_sop_merah' => false,
                        'desc' => 'Tugas KS | Kategori: ' . $m->kategori . ' | Sifat: ' . $m->sifat,
                        'url' => \App\Filament\Staff\Resources\ProgramKepsekResource::getUrl('index'),
                    ];
                });
            } elseif ($pillar->order == 3) {
                $tasks = \App\Models\ProgramKurikulum::all()->map(function($m) {
                    return (object)[
                        'id' => 'dynamic_kurikulum_' . $m->id,
                        'name' => $m->nama_program,
                        'status' => $m->status_pelaksanaan === 'Selesai' ? 'selesai' : 'belum',
                        'code' => 'WKr',
                        'assigned_role' => 'WKr',
                        'is_sop_merah' => false,
                        'desc' => 'Program Kurikulum | Kategori: ' . $m->kategori . ' | Sifat: ' . $m->sifat,
                        'url' => \App\Filament\Staff\Resources\ProgramKurikulumResource::getUrl('view', ['record' => $m->id]),
                    ];
                });
            } elseif ($pillar->order == 5) {
                $tasks = \App\Models\ProgramKesiswaan::all()->map(function($m) {
                    return (object)[
                        'id' => 'dynamic_kesiswaan_' . $m->id,
                        'name' => $m->nama_program,
                        'status' => $m->status_pelaksanaan === 'Selesai' ? 'selesai' : 'belum',
                        'code' => 'WKs',
                        'assigned_role' => 'WKs',
                        'is_sop_merah' => false,
                        'desc' => 'Program Kesiswaan | Kategori: ' . $m->kategori . ' | Sifat: ' . $m->sifat,
                        'url' => \App\Filament\Staff\Resources\ProgramKesiswaanResource::getUrl('view', ['record' => $m->id]),
                    ];
                });
            } elseif ($pillar->order == 6) {
                $tasks = \App\Models\ProgramSarpras::all()->map(function($m) {
                    return (object)[
                        'id' => 'dynamic_sarpras_' . $m->id,
                        'name' => $m->nama_program,
                        'status' => $m->status_pelaksanaan === 'Selesai' ? 'selesai' : 'belum',
                        'code' => 'Sar',
                        'assigned_role' => 'Sar',
                        'is_sop_merah' => false,
                        'desc' => 'Program Sarpras | Kategori: ' . $m->kategori . ' | Sifat: ' . $m->sifat,
                        'url' => \App\Filament\Staff\Resources\ProgramSarprasResource::getUrl('index'),
                    ];
                });
            } elseif ($pillar->order == 8) {
                $tasks = \App\Models\ProgramHumas::all()->map(function($m) {
                    return (object)[
                        'id' => 'dynamic_humas_' . $m->id,
                        'name' => $m->nama_program,
                        'status' => $m->status_pelaksanaan === 'Selesai' ? 'selesai' : 'belum',
                        'code' => 'Hm',
                        'assigned_role' => 'Hm',
                        'is_sop_merah' => false,
                        'desc' => 'Program Humas | Kategori: ' . $m->kategori . ' | Sifat: ' . $m->sifat,
                        'url' => \App\Filament\Staff\Resources\ProgramHumasResource::getUrl('index'),
                    ];
                });
            } elseif ($pillar->order == 9) {
                $tasks = \App\Models\BkWorkProgram::all()->map(function($m) {
                    return (object)[
                        'id' => 'dynamic_bk_' . $m->id,
                        'name' => $m->judul_program,
                        'status' => $m->status === 'Terlaksana' ? 'selesai' : 'belum',
                        'code' => 'BK',
                        'assigned_role' => 'BK',
                        'is_sop_merah' => false,
                        'desc' => 'Layanan BK | Bidang: ' . $m->bidang_layanan . ' | Pelaksanaan: ' . $m->tipe_pelaksanaan,
                        'url' => \App\Filament\Staff\Resources\BkWorkProgramResource::getUrl('view', ['record' => $m->id]),
                    ];
                });
            }

            // Filter for non-KS users
            if (!$isKS && count($codes) > 0 && !is_object(collect($tasks)->first()) ) {
                 // For static tasks
            } elseif (!$isKS && count($codes) > 0) {
                 // For dynamic tasks
                 $tasks = collect($tasks)->filter(function($task) use ($codes) {
                    foreach ($codes as $code) {
                        if (str_contains($task->assigned_role, $code)) {
                            return true;
                        }
                    }
                    return false;
                });
            }

            $total = collect($tasks)->count();
            if (!$isKS && $total == 0) continue;

            $selesai = collect($tasks)->where('status', 'selesai')->count();
            $percentage = $total > 0 ? round(($selesai / $total) * 100) : 0;

            $totalAll += $total;
            $totalSelesaiAll += $selesai;

            $data[] = [
                'id' => $pillar->id,
                'order' => $pillar->order,
                'name' => $pillar->name,
                'total' => $total,
                'selesai' => $selesai,
                'percentage' => $percentage,
                'tasks' => $tasks,
            ];
        }

        return $this->cachedPillarsData = $data;
    }

    public function getStudentCountProperty()
    {
        if ($this->cachedStudentCount !== null) {
            return $this->cachedStudentCount;
        }

        $instId = Auth::user()?->institution_id;
        return $this->cachedStudentCount = \App\Models\Student::withoutGlobalScopes()
            ->when($instId, fn ($q) => $q->where('institution_id', $instId))
            ->count();
    }

    public function getTeacherCountProperty()
    {
        if ($this->cachedTeacherCount !== null) {
            return $this->cachedTeacherCount;
        }

        $instId = Auth::user()?->institution_id;
        $count = \App\Models\User::whereHas('roles', function($q) {
            $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas']);
        })->where('institution_id', $instId)->count();

        $kpiService = new \App\Services\TeacherKpiService();
        $avgKpi = $kpiService->getInstitutionAverage($instId ?? 0, 'harian');

        return $this->cachedTeacherCount = $count . ' / ' . $avgKpi . '%';
    }

    public function getTopTeachersProperty()
    {
        if ($this->cachedTopTeachers !== null) {
            return $this->cachedTopTeachers;
        }

        $institutionId = Auth::user()?->institution_id;
        $kpiService = new \App\Services\TeacherKpiService();

        $teachers = \App\Models\User::with('roles')
            ->where('institution_id', $institutionId)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['guru_mapel', 'guru_alquran', 'wali_kelas', 'guru_kelas']);
            })
            ->get();

        $leaderboard = collect();
        foreach ($teachers as $teacher) {
            $persentase = $kpiService->calculateForTeacher($teacher->id, 'bulanan');
            $tugasSelesai = $kpiService->getCompletedTasksCount($teacher->id, 'bulanan');

            $leaderboard->push((object) [
                'teacher_id' => $teacher->id,
                'teacher' => $teacher,
                'tugas_selesai' => $tugasSelesai,
                'persentase' => $persentase,
            ]);
        }

        return $this->cachedTopTeachers = $leaderboard
            ->sortByDesc(fn ($item) => [$item->persentase, $item->tugas_selesai])
            ->take(3)
            ->values();
    }

    public function getAiSummaryProperty()
    {
        if ($this->cachedAiSummary !== null) {
            return $this->cachedAiSummary;
        }

        $pillars = $this->pillarsData;
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
        
        $summary .= "👉 **Manajemen Sekolah:** Ketercapaian global manajemen berada di angka **{$percentage}%**. ";
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

        // 2. Kinerja Guru Note (Khusus KS & Yayasan)
        $canAccessRanking = \App\Filament\Staff\Pages\TeacherPerformanceDashboard::canAccess();
        if ($canAccessRanking && $topTeachers->count() > 0) {
            $first = $topTeachers->first();
            $summary .= "\n\n👉 **Kinerja Guru (Bulan Ini):** Apresiasi tertinggi diberikan kepada **{$first->teacher->name}** yang memimpin dengan penyelesaian {$first->tugas_selesai} tugas ({$first->persentase}% tuntas). Tingkat kedisiplinan guru dalam menyelesaikan *To-Do List* sudah menunjukkan tren yang positif!";
        }

        return $this->cachedAiSummary = $summary;
    }

    public function getLatestNewsProperty()
    {
        if ($this->cachedLatestNews !== null) {
            return $this->cachedLatestNews;
        }

        $news = \App\Models\News::where('is_published', true)->latest()->first();
        if ($news) {
            return $this->cachedLatestNews = "**" . $news->title . "**\n\n" . \Illuminate\Support\Str::limit(strip_tags($news->content), 100);
        }
        return $this->cachedLatestNews = "-";
    }

    public function getUpcomingSupervisionsProperty()
    {
        if ($this->cachedUpcomingSupervisions !== null) {
            return $this->cachedUpcomingSupervisions;
        }

        $user = Auth::user();
        if (!$user) {
            return $this->cachedUpcomingSupervisions = collect();
        }

        $query = \App\Models\TeacherSupervision::with(['teacher', 'supervisor'])
            ->where('status', 'dijadwalkan')
            ->orderBy('jadwal', 'asc');

        if ($user->hasAnyRole(['kepala_sekolah', 'waka_kurikulum', 'superadmin'])) {
            return $this->cachedUpcomingSupervisions = $query->get();
        }

        return $this->cachedUpcomingSupervisions = $query->where('teacher_id', $user->id)->get();
    }

    public function getGoodNewsProperty()
    {
        if ($this->cachedGoodNews !== null) {
            return $this->cachedGoodNews;
        }

        $pillars = $this->pillarsData;
        $totalAll = collect($pillars)->sum('total');
        $totalSelesaiAll = collect($pillars)->sum('selesai');
        $percentage = $totalAll > 0 ? round(($totalSelesaiAll / $totalAll) * 100) : 0;
        
        $terbaik = collect($pillars)->sortByDesc('percentage')->first();
        $topTeachers = collect($this->topTeachers)->first();

        $news = "✨ **Progress Keseluruhan:** {$percentage}% dari seluruh tugas operasional dan manajerial telah tercapai.\n\n";
        
        if ($terbaik && $terbaik['percentage'] > 0) {
            $news .= "🏆 **Pilar Unggulan:** Pilar {$terbaik['order']} ({$terbaik['name']}) memimpin dengan ketercapaian {$terbaik['percentage']}%.\n\n";
        }
        
        if (\App\Filament\Staff\Pages\TeacherPerformanceDashboard::canAccess() && $topTeachers) {
            $news .= "🌟 **Apresiasi Guru:** {$topTeachers->teacher->name} memimpin kinerja bulan ini dengan {$topTeachers->persentase}% penyelesaian tugas!";
        }

        return $this->cachedGoodNews = $news;
    }

    public function getBadNewsProperty()
    {
        if ($this->cachedBadNews !== null) {
            return $this->cachedBadNews;
        }

        $pillars = $this->pillarsData;
        $tertinggal = collect($pillars)->filter(fn($p) => $p['total'] > 0)->sortBy('percentage')->first();
        
        $news = "";
        if ($tertinggal && $tertinggal['percentage'] < 50) {
            $news .= "⚠️ **Perlu Perhatian:** Pilar {$tertinggal['order']} ({$tertinggal['name']}) baru mencapai {$tertinggal['percentage']}%. Memerlukan intervensi segera.\n\n";
        }

        $emptyClasses = \App\Models\EmptyClassLog::whereDate('created_at', \Carbon\Carbon::today())->count();
        if ($emptyClasses > 0) {
            $news .= "🚨 **Kelas Kosong:** Terdapat {$emptyClasses} laporan kelas kosong hari ini.\n\n";
        }

        $pendingLeaves = \App\Models\TeacherLeaveRequest::where('status', 'diajukan')->count();
        if ($pendingLeaves > 0) {
            $news .= "⏳ **Perizinan Guru:** {$pendingLeaves} pengajuan izin menunggu persetujuan Kepala Sekolah/Wakakur.\n\n";
        }

        if (empty(trim($news))) {
            $news = "✅ Tidak ada isu mendesak saat ini. Seluruh indikator berada dalam batas aman.";
        }

        return $this->cachedBadNews = $news;
    }

    public function markTask($taskId, $status)
    {
        // Invalidate memoized cache so UI reflects change immediately
        $this->cachedPillarsData = null;
        $this->cachedAiSummary = null;
        $this->cachedGoodNews = null;
        $this->cachedBadNews = null;

        $user = Auth::user();
        $codes = [];
        if ($user->hasRole('kepala_sekolah') || $user->hasRole('superadmin')) $codes[] = 'KS';
        if ($user->hasRole('waka_kurikulum')) $codes[] = 'WKr';
        if ($user->hasRole('waka_kesiswaan')) $codes[] = 'WKs';
        if ($user->hasRole('sarpras')) $codes[] = 'SP';
        if ($user->hasRole('humas')) $codes[] = 'Hm';
        if ($user->hasRole('bk')) $codes[] = 'BK';
        if ($user->hasRole('tata_usaha')) $codes[] = 'TU';

        $isKS = in_array('KS', $codes);

        // HANDLE DYNAMIC TASKS
        if (is_string($taskId) && str_starts_with($taskId, 'dynamic_')) {
            $parts = explode('_', $taskId);
            $type = $parts[1]; // 'kurikulum', 'kesiswaan', 'humas', 'bk', 'ks'
            $id = $parts[2];
            
            $hasAccess = $isKS;
            if (!$isKS) {
                if ($type == 'kurikulum' && in_array('WKr', $codes)) $hasAccess = true;
                if ($type == 'kesiswaan' && in_array('WKs', $codes)) $hasAccess = true;
                if ($type == 'humas' && in_array('Hm', $codes)) $hasAccess = true;
                if ($type == 'bk' && in_array('BK', $codes)) $hasAccess = true;
                if ($type == 'ks' && in_array('KS', $codes)) $hasAccess = true;
            }

            if ($hasAccess) {
                if ($type == 'kurikulum') {
                    $t = \App\Models\ProgramKurikulum::find($id);
                    if ($t) { $t->status_pelaksanaan = $status === 'selesai' ? 'Selesai' : 'Sedang Berjalan'; $t->save(); }
                } elseif ($type == 'kesiswaan') {
                    $t = \App\Models\ProgramKesiswaan::find($id);
                    if ($t) { $t->status_pelaksanaan = $status === 'selesai' ? 'Selesai' : 'Sedang Berjalan'; $t->save(); }
                } elseif ($type == 'humas') {
                    $t = \App\Models\HumasProgram::find($id);
                    if ($t) { $t->status = $status === 'selesai' ? 'sudah' : 'belum'; $t->save(); }
                } elseif ($type == 'bk') {
                    $t = \App\Models\BkWorkProgram::find($id);
                    if ($t) { $t->status = $status === 'selesai' ? 'Terlaksana' : 'Sedang Berjalan'; $t->save(); }
                } elseif ($type == 'ks') {
                    $t = \App\Models\WorkProgram::find($id);
                    if ($t) { $t->status_pelaksanaan = $status === 'selesai' ? 'selesai' : 'berjalan'; $t->save(); }
                }

                Notification::make()
                    ->title('Status Program Diperbarui')
                    ->body('Status program terkait telah berhasil disinkronisasi ke modul.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Akses Ditolak')
                    ->body('Anda tidak memiliki wewenang untuk mengubah status program ini.')
                    ->danger()
                    ->send();
            }
            return;
        }

        // HANDLE STATIC SOP TASKS
        $task = \App\Models\SchoolPillarTask::find($taskId);
        if ($task) {
            $hasAccess = $isKS;
            
            if (!$isKS) {
                foreach ($codes as $code) {
                    if (str_contains($task->assigned_role, $code)) {
                        $hasAccess = true;
                        break;
                    }
                }
            }

            if ($hasAccess) {
                $task->update([
                    'status' => $status,
                    'completed_at' => $status === 'selesai' ? now() : null,
                    'completed_by' => $status === 'selesai' ? Auth::id() : null,
                ]);

                Notification::make()
                    ->title('Status Diperbarui')
                    ->body('Progres ketercapaian pilar berhasil disinkronisasi.')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Akses Ditolak')
                    ->body('Anda tidak memiliki wewenang untuk menyelesaikan tugas ini.')
                    ->danger()
                    ->send();
            }
        }
    }
}
