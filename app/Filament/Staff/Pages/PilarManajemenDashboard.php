<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use App\Models\SchoolPillar;
use App\Models\SchoolPillarTask;
use Illuminate\Support\Facades\Auth;

class PilarManajemenDashboard extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Manajemen Sekolah';
    protected static ?string $navigationLabel = 'Dashboard 9 Pilar';
    protected static ?string $title = 'Progress 9 Pilar Manajemen Sekolah';
    protected static string $view = 'filament.staff.pages.pilar-manajemen-dashboard';

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole([
            'superadmin', 'kepala_sekolah', 'waka_kurikulum', 'waka_kesiswaan', 
            'sarpras', 'humas', 'bk', 'tata_usaha'
        ]);
    }

    protected function getUserPicCodes(): array
    {
        $user = Auth::user();
        $codes = [];
        if ($user->hasRole('kepala_sekolah') || $user->hasRole('superadmin')) $codes[] = 'KS';
        if ($user->hasRole('waka_kurikulum')) $codes[] = 'WKr';
        if ($user->hasRole('waka_kesiswaan')) $codes[] = 'WKs';
        if ($user->hasRole('sarpras')) $codes[] = 'SP';
        if ($user->hasRole('humas')) $codes[] = 'Hm';
        if ($user->hasRole('bk')) $codes[] = 'BK';
        if ($user->hasRole('tata_usaha')) $codes[] = 'TU';
        return $codes;
    }

    public function getPillarsProperty()
    {
        $picCodes = $this->getUserPicCodes();
        $isKS = in_array('KS', $picCodes);

        $pillars = SchoolPillar::orderBy('order')->with(['tasks' => function($q) {
            $q->orderBy('order');
        }])->get();

        if (!$isKS) {
            // Filter tasks based on assigned roles for non-KS users
            $pillars->each(function($pillar) use ($picCodes) {
                $pillar->setRelation('tasks', $pillar->tasks->filter(function($task) use ($picCodes) {
                    foreach ($picCodes as $code) {
                        if (str_contains($task->assigned_role, $code)) {
                            return true;
                        }
                    }
                    return false;
                }));
            });

            // Hanya tampilkan pilar yang memiliki tugas untuk role tersebut
            $pillars = $pillars->filter(function($pillar) {
                return $pillar->tasks->count() > 0;
            });
        }

        return $pillars;
    }

    public function getProgressDataProperty()
    {
        $pillars = $this->pillars;
        $data = [];

        foreach ($pillars as $pillar) {
            $total = $pillar->tasks->count();
            $selesai = $pillar->tasks->where('status', 'selesai')->count();
            $percentage = $total > 0 ? round(($selesai / $total) * 100) : 0;

            $data[] = [
                'id' => $pillar->id,
                'name' => $pillar->name,
                'total' => $total,
                'selesai' => $selesai,
                'percentage' => $percentage,
            ];
        }

        return $data;
    }

    public function markTask($taskId, $status)
    {
        $task = SchoolPillarTask::find($taskId);
        if ($task) {
            // Cek apakah user punya hak untuk klik ini
            $picCodes = $this->getUserPicCodes();
            $isKS = in_array('KS', $picCodes);
            
            $hasAccess = $isKS;
            if (!$isKS) {
                foreach ($picCodes as $code) {
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
            }
        }
    }
}
