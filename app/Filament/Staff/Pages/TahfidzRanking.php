<?php

namespace App\Filament\Staff\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

class TahfidzRanking extends Page
{
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['guru_alquran', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']) ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasAnyRole(['guru_alquran', 'waka_kurikulum', 'kepala_sekolah', 'superadmin']);
    }

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static string $view = 'filament.staff.pages.tahfidz-ranking';
    protected static ?string $navigationGroup = 'Kesiswaan & Karakter';
    protected static ?string $title = 'Peringkat Tahfidz';
    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasRole('guru_alquran')) {
            return 'Fitur Guru Al-Qur\'an';
        }
        return 'Kesiswaan & Karakter';
    }

    protected function getViewData(): array
    {
        // Get all classrooms for this institution
        $user = auth()->user();
        $classrooms = Classroom::when($user?->institution_id && !$user->hasRole('superadmin'), fn ($q) => $q->where('institution_id', $user->institution_id))
            ->orderBy('name')
            ->get();

        $rankings = [];

        foreach ($classrooms as $class) {
            // Get students in this class, count their ziyadah achievements
            $students = Student::where('classroom_id', $class->id)
                ->withCount(['quranAchievements as ziyadah_count' => function (Builder $query) {
                    $query->where(function ($q) {
                        $q->where('type', 'ziyadah')
                          ->orWhere('type', 'like', '%ziyadah%');
                    });
                }])
                ->orderByDesc('ziyadah_count')
                ->get();
            
            $rankings[$class->name] = $students;
        }

        return [
            'rankings' => $rankings,
        ];
    }
}
