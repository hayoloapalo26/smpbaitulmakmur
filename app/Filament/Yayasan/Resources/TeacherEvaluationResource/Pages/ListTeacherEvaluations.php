<?php

namespace App\Filament\Yayasan\Resources\TeacherEvaluationResource\Pages;

use App\Filament\Yayasan\Resources\TeacherEvaluationResource;
use App\Models\Institution;
use App\Models\User;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTeacherEvaluations extends ListRecords
{
    protected static string $resource = TeacherEvaluationResource::class;

    public function getTabs(): array
    {
        $teacherRoles = array_merge([
            'guru_kelas', 'guru_mapel', 'wali_kelas',
            'bk', 'guru_piket', 'waka_kurikulum', 'waka_kesiswaan',
            'sarpras', 'humas',
        ], \App\Models\User::QURAN_TEACHER_ROLES);

        $tabs = [
            'all' => Tab::make('Semua Lembaga')
                ->badge(fn () => User::withoutGlobalScopes()
                    ->whereHas('roles', fn ($q) => $q->whereIn('name', $teacherRoles))
                    ->count()),
        ];

        $institutions = Institution::where('id', '!=', 1)->orderBy('id')->get();
        foreach ($institutions as $inst) {
            $tabs['inst_' . $inst->id] = Tab::make($inst->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('institution_id', $inst->id))
                ->badge(fn () => User::withoutGlobalScopes()
                    ->where('institution_id', $inst->id)
                    ->whereHas('roles', fn ($q) => $q->whereIn('name', $teacherRoles))
                    ->count());
        }

        return $tabs;
    }

    protected function getActions(): array
    {
        return [];
    }
}
