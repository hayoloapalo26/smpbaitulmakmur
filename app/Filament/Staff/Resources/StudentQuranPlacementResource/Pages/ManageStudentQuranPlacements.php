<?php

namespace App\Filament\Staff\Resources\StudentQuranPlacementResource\Pages;

use App\Filament\Staff\Resources\StudentQuranPlacementResource;
use App\Models\Student;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ManageStudentQuranPlacements extends ManageRecords
{
    protected static string $resource = StudentQuranPlacementResource::class;

    protected function getBaseStudentQuery(): Builder
    {
        $user = Auth::user();
        $query = Student::query();

        if ($user && !$user->hasRole('superadmin') && $user->institution_id != 1) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('students', 'institution_id')) {
                $query->where('students.institution_id', $user->institution_id);
            } else {
                $query->whereHas('classroom', fn ($cq) => $cq->where('institution_id', $user->institution_id));
            }
        }

        return $query;
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Siswa')
                ->icon('heroicon-o-users')
                ->badge(fn () => $this->getBaseStudentQuery()->count()),

            'belum' => Tab::make('⚠️ Belum Ditempatkan')
                ->icon('heroicon-o-exclamation-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('kelas_quran'))
                ->badge(fn () => $this->getBaseStudentQuery()->whereNull('kelas_quran')->count())
                ->badgeColor('danger'),

            'yanbua' => Tab::make('Yanbu\'a')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kelas_quran', 'yanbua'))
                ->badge(fn () => $this->getBaseStudentQuery()->where('kelas_quran', 'yanbua')->count())
                ->badgeColor('purple'),

            'tahsin' => Tab::make('Tahsin')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kelas_quran', 'tahsin'))
                ->badge(fn () => $this->getBaseStudentQuery()->where('kelas_quran', 'tahsin')->count())
                ->badgeColor('warning'),

            'tahfidz_dasar' => Tab::make('Tahfidz Dasar')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kelas_quran', 'tahfidz_dasar'))
                ->badge(fn () => $this->getBaseStudentQuery()->where('kelas_quran', 'tahfidz_dasar')->count())
                ->badgeColor('success'),

            'tahfidz_lanjutan' => Tab::make('Tahfidz Lanjutan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('kelas_quran', 'tahfidz_lanjutan'))
                ->badge(fn () => $this->getBaseStudentQuery()->where('kelas_quran', 'tahfidz_lanjutan')->count())
                ->badgeColor('info'),
        ];
    }
}
