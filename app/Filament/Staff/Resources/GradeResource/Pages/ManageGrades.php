<?php

namespace App\Filament\Staff\Resources\GradeResource\Pages;

use App\Filament\Staff\Resources\GradeResource;
use App\Models\Grade;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageGrades extends ManageRecords
{
    protected static string $resource = GradeResource::class;

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Nilai')
                ->icon('heroicon-o-list-bullet'),

            'perlu_remedial' => Tab::make('⚠️ Perlu Remedial (< 75)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('score', '<', 75)->where('is_remedial', false))
                ->badge(fn () => $this->getModel()::where('score', '<', 75)->where('is_remedial', false)->count())
                ->badgeColor('danger'),

            'remedial_selesai' => Tab::make('🔄 Remedial Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_remedial', true))
                ->badge(fn () => $this->getModel()::where('is_remedial', true)->count())
                ->badgeColor('warning'),

            'siap_pengayaan' => Tab::make('✅ Tuntas (Siap Pengayaan)')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($q) {
                    $q->where('score', '>=', 75)
                      ->orWhere(fn ($sq) => $sq->where('is_remedial', true)->where('nilai_remedial', '>=', 75));
                })->where('is_pengayaan', false))
                ->badge(fn () => $this->getModel()::where(function ($q) {
                    $q->where('score', '>=', 75)
                      ->orWhere(fn ($sq) => $sq->where('is_remedial', true)->where('nilai_remedial', '>=', 75));
                })->where('is_pengayaan', false)->count())
                ->badgeColor('success'),

            'pengayaan_selesai' => Tab::make('⭐ Pengayaan Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_pengayaan', true))
                ->badge(fn () => $this->getModel()::where('is_pengayaan', true)->count())
                ->badgeColor('warning'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('rumah_nilai')
                ->label('🏠 Buka Lembar Input Nilai Per Mapel (Batch Grid)')
                ->color('success')
                ->icon('heroicon-o-academic-cap')
                ->url(fn () => url('/staff/input-nilai-mapel')),
            Actions\CreateAction::make()
                ->label('Input Nilai Satuan')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
