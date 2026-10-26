<?php

namespace App\Filament\Yayasan\Resources\PermintaanUkgResource\Pages;

use App\Filament\Yayasan\Resources\PermintaanUkgResource;
use App\Models\Institution;
use App\Models\TeacherCompetencyTest;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListPermintaanUkgs extends ListRecords
{
    protected static string $resource = PermintaanUkgResource::class;

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Semua Lembaga')
                ->badge(fn () => TeacherCompetencyTest::withoutGlobalScopes()->count()),
        ];

        $institutions = Institution::where('id', '!=', 1)->orderBy('id')->get();
        foreach ($institutions as $inst) {
            $tabs['inst_' . $inst->id] = Tab::make($inst->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('institution_id', $inst->id))
                ->badge(fn () => TeacherCompetencyTest::withoutGlobalScopes()->where('institution_id', $inst->id)->count());
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Input Penilaian Kinerja Guru (PKG) Baru')
                ->icon('heroicon-o-plus-circle')
                ->color('success'),
        ];
    }
}